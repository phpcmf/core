<?php

namespace Cmf\Http\Access;

use Cmf\Http\AccessToken;
use Cmf\User\Access\AbstractPolicy;
use Cmf\User\User;

class AccessTokenPolicy extends AbstractPolicy
{
    public function revoke(User $actor, AccessToken $token)
    {
        if ($token->user_id === $actor->id || $actor->hasPermission('moderateAccessTokens')) {
            return $this->allow();
        }
    }
}
