<?php

namespace Cmf\User;

use Illuminate\Database\Eloquent\Builder;

class UserRepository
{
    /**
     * 获取 users 表的新查询生成器
     *
     * @return Builder<User>
     */
    public function query()
    {
        return User::query();
    }

    /**
     * 按 ID 查找用户，可以选择确保该用户对特定用户可见，或者引发异常。
     *
     * @param int|string $id
     * @param User|null $actor
     * @return User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = $this->query()->where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * 按用户名查找用户，可以选择确保该用户对特定用户可见，否则引发异常
     *
     * @param string $username
     * @param User|null $actor
     * @return User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailByUsername($username, User $actor = null)
    {
        $query = $this->query()->where('username', $username);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * 通过标识（用户名或电子邮件）查找用户
     *
     * @param string $identification
     * @return User|null
     */
    public function findByIdentification($identification)
    {
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return $this->query()->where($field, $identification)->first();
    }

    /**
     * 通过电子邮件查找用户
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail($email)
    {
        return $this->query()->where('email', $email)->first();
    }

    /**
     * 获取具有给定用户名的用户的 ID
     *
     * @param string $username
     * @param User|null $actor
     * @return int|null
     */
    public function getIdForUsername($username, User $actor = null)
    {
        $query = $this->query()->where('username', $username);

        return $this->scopeVisibleTo($query, $actor)->value('id');
    }

    public function getIdsForUsernames(array $usernames, User $actor = null): array
    {
        $query = $this->query()->whereIn('username', $usernames);

        return $this->scopeVisibleTo($query, $actor)->pluck('id')->all();
    }

    /**
     * 通过将一串单词与用户名进行匹配来查找用户，也可以确保它们对特定用户可见
     *
     * @param string $string
     * @param User|null $actor
     * @return array
     */
    public function getIdsForUsername($string, User $actor = null)
    {
        $string = $this->escapeLikeString($string);

        $query = $this->query()->where('username', 'like', '%'.$string.'%')
            ->orderByRaw('username = ? desc', [$string])
            ->orderByRaw('username like ? desc', [$string.'%']);

        return $this->scopeVisibleTo($query, $actor)->pluck('id')->all();
    }

    /**
     * 将查询范围限定为仅包含对用户可见的记录
     *
     * @param Builder<User> $query
     * @param User|null $actor
     * @return Builder<User>
     */
    protected function scopeVisibleTo(Builder $query, User $actor = null)
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }

    /**
     * 转义可在 LIKE 查询中用作通配符的特殊字符
     *
     * @param string $string
     * @return string
     */
    private function escapeLikeString($string)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $string);
    }
}
