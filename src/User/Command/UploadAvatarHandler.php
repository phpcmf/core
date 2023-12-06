<?php

namespace Cmf\User\Command;

use Cmf\Foundation\DispatchEventsTrait;
use Cmf\User\AvatarUploader;
use Cmf\User\AvatarValidator;
use Cmf\User\Event\AvatarSaving;
use Cmf\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Intervention\Image\ImageManager;

class UploadAvatarHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Cmf\User\UserRepository
     */
    protected $users;

    /**
     * @var AvatarUploader
     */
    protected $uploader;

    /**
     * @var \Cmf\User\AvatarValidator
     */
    protected $validator;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param AvatarUploader $uploader
     * @param AvatarValidator $validator
     */
    public function __construct(Dispatcher $events, UserRepository $users, AvatarUploader $uploader, AvatarValidator $validator, ImageManager $imageManager)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploader = $uploader;
        $this->validator = $validator;
        $this->imageManager = $imageManager;
    }

    /**
     * @param UploadAvatar $command
     * @return \Cmf\User\User
     * @throws \Cmf\User\Exception\PermissionDeniedException
     * @throws \Cmf\Foundation\ValidationException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        $actor->assertCan('uploadAvatar', $user);

        $this->validator->assertValid(['avatar' => $command->file]);

        $image = $this->imageManager->make($command->file->getStream()->getMetadata('uri'));

        $this->events->dispatch(
            new AvatarSaving($user, $actor, $image)
        );

        $this->uploader->upload($user, $image);

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
