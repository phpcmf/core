<?php

namespace Cmf\Http;

use Cmf\Database\AbstractModel;
use Cmf\User\User;

/**
 * @template T of AbstractModel
 */
interface SlugDriverInterface
{
    /**
     * @param T $instance
     */
    public function toSlug(AbstractModel $instance): string;

    /**
     * @return T
     */
    public function fromSlug(string $slug, User $actor): AbstractModel;
}
