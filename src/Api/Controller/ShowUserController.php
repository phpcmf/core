<?php

namespace Cmf\Api\Controller;

use Cmf\Api\Serializer\CurrentUserSerializer;
use Cmf\Api\Serializer\UserSerializer;
use Cmf\Http\RequestUtil;
use Cmf\Http\SlugManager;
use Cmf\User\User;
use Cmf\User\UserRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowUserController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = UserSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param SlugManager $slugManager
     * @param UserRepository $users
     */
    public function __construct(SlugManager $slugManager, UserRepository $users)
    {
        $this->slugManager = $slugManager;
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        if (Arr::get($request->getQueryParams(), 'bySlug', false)) {
            $user = $this->slugManager->forResource(User::class)->fromSlug($id, $actor);
        } else {
            $user = $this->users->findOrFail($id, $actor);
        }

        if ($actor->id === $user->id) {
            $this->serializer = CurrentUserSerializer::class;
        }

        return $user;
    }
}
