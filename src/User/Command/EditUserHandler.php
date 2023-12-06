<?php

namespace Cmf\User\Command;

use Cmf\Foundation\DispatchEventsTrait;
use Cmf\User\Event\GroupsChanged;
use Cmf\User\Event\Saving;
use Cmf\User\User;
use Cmf\User\UserRepository;
use Cmf\User\UserValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EditUserHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Cmf\User\UserRepository
     */
    protected $users;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param \Cmf\User\UserRepository $users
     * @param UserValidator $validator
     */
    public function __construct(Dispatcher $events, UserRepository $users, UserValidator $validator)
    {
        $this->events = $events;
        $this->users = $users;
        $this->validator = $validator;
    }

    /**
     * @param EditUser $command
     * @return User
     * @throws \Cmf\User\Exception\PermissionDeniedException
     * @throws ValidationException
     */
    public function handle(EditUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $user = $this->users->findOrFail($command->userId, $actor);

        $isSelf = $actor->id === $user->id;

        $attributes = Arr::get($data, 'attributes', []);
        $relationships = Arr::get($data, 'relationships', []);
        $validate = [];

        if (isset($attributes['username'])) {
            $actor->assertCan('editCredentials', $user);
            $user->rename($attributes['username']);
        }

        if (isset($attributes['email'])) {
            if ($isSelf) {
                $user->requestEmailChange($attributes['email']);

                if ($attributes['email'] !== $user->email) {
                    $validate['email'] = $attributes['email'];
                }
            } else {
                $actor->assertCan('editCredentials', $user);
                $user->changeEmail($attributes['email']);
            }
        }

        if (! empty($attributes['isEmailConfirmed'])) {
            $actor->assertAdmin();
            $user->activate();
        }

        if (isset($attributes['password'])) {
            $actor->assertCan('editCredentials', $user);
            $user->changePassword($attributes['password']);

            $validate['password'] = $attributes['password'];
        }

        if (! empty($attributes['markedAllAsReadAt'])) {
            $actor->assertPermission($isSelf);
            $user->markAllAsRead();
        }

        if (! empty($attributes['preferences'])) {
            $actor->assertPermission($isSelf);

            foreach ($attributes['preferences'] as $k => $v) {
                $user->setPreference($k, $v);
            }
        }

        if (isset($relationships['groups']['data']) && is_array($relationships['groups']['data'])) {
            $actor->assertCan('editGroups', $user);

            $oldGroups = $user->groups()->get()->all();
            $oldGroupIds = Arr::pluck($oldGroups, 'id');

            $newGroupIds = [];
            foreach ($relationships['groups']['data'] as $group) {
                if ($id = Arr::get($group, 'id')) {
                    $newGroupIds[] = $id;
                }
            }

            // Ensure non-admins aren't adding/removing admins
            $adminChanged = in_array('1', array_diff($oldGroupIds, $newGroupIds)) || in_array('1', array_diff($newGroupIds, $oldGroupIds));
            $actor->assertPermission(! $adminChanged || $actor->isAdmin());

            $user->raise(
                new GroupsChanged($user, $oldGroups)
            );

            $user->afterSave(function (User $user) use ($newGroupIds) {
                $user->groups()->sync($newGroupIds);
                $user->unsetRelation('groups');
            });
        }

        $this->events->dispatch(
            new Saving($user, $actor, $data)
        );

        $this->validator->setUser($user);
        $this->validator->assertValid(array_merge($user->getDirty(), $validate));

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
