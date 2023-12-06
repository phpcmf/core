<?php

namespace Cmf\User;

use Carbon\Carbon;
use DomainException;
use Cmf\Database\AbstractModel;
use Cmf\Database\ScopeVisibilityTrait;
use Cmf\Discussion\Discussion;
use Cmf\Foundation\EventGeneratorTrait;
use Cmf\Group\Group;
use Cmf\Group\Permission;
use Cmf\Http\AccessToken;
use Cmf\Notification\Notification;
use Cmf\Post\Post;
use Cmf\User\DisplayName\DriverInterface;
use Cmf\User\Event\Activated;
use Cmf\User\Event\AvatarChanged;
use Cmf\User\Event\Deleted;
use Cmf\User\Event\EmailChanged;
use Cmf\User\Event\EmailChangeRequested;
use Cmf\User\Event\PasswordChanged;
use Cmf\User\Event\Registered;
use Cmf\User\Event\Renamed;
use Cmf\User\Exception\NotAuthenticatedException;
use Cmf\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Arr;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

/**
 * @property int $id
 * @property string $username
 * @property string $display_name
 * @property string $email
 * @property bool $is_email_confirmed
 * @property string $password
 * @property string|null $avatar_url
 * @property array $preferences
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property \Carbon\Carbon|null $marked_all_as_read_at
 * @property \Carbon\Carbon|null $read_notifications_at
 * @property int $discussion_count
 * @property int $comment_count
 */
class User extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasEagerLimit;

    /**
     * 应更改为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'joined_at',
        'last_seen_at',
        'marked_all_as_read_at',
        'read_notifications_at'
    ];

    /**
     * 此用户拥有的权限数组
     *
     * @var string[]|null
     */
    protected $permissions = null;

    /**
     * 可调用对象数组，用户组列表在返回之前通过每个数组传递
     */
    protected static $groupProcessors = [];

    /**
     * 一组已注册的用户首选项。每个首选项都使用一个键进行定义，其值是一个包含以下键的数组：
     *
     * - transformer：限制首选项值的回调 default：如果未设置首选项，则为默认值
     *
     * @var array
     */
    protected static $preferences = [];

    /**
     * 用于获取显示名称的驱动程序
     *
     * @var DriverInterface
     */
    protected static $displayNameDriver;

    /**
     * 用于哈希处理密码的哈希程序
     *
     * @var Hasher
     */
    protected static $hasher;

    /**
     * 出入口
     *
     * @var Access\Gate
     */
    protected static $gate;

    /**
     * 用于检查密码的回调
     *
     * @var array
     */
    protected static $passwordCheckers;

    /**
     * 与当前 `updateLastSeen()`之前的 `last_seen` 属性值的差异将更新数据库上的属性。以秒为单位测量
     */
    private const LAST_SEEN_UPDATE_DIFF = 180;

    /**
     * 启动模型
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // 不允许删除 root 管理员。
        static::deleting(function (self $user) {
            if ($user->id == 1) {
                throw new DomainException('Cannot delete the root admin');
            }
        });

        static::deleted(function (self $user) {
            $user->raise(new Deleted($user));

            Notification::whereSubject($user)->delete();
        });
    }

    /**
     * 注册一个新用户
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return static
     */
    public static function register($username, $email, $password)
    {
        $user = new static;

        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        $user->joined_at = Carbon::now();

        $user->raise(new Registered($user));

        return $user;
    }

    /**
     * @param Access\Gate $gate
     */
    public static function setGate($gate)
    {
        static::$gate = $gate;
    }

    /**
     * 设置显示名称驱动程序
     *
     * @param DriverInterface $driver
     */
    public static function setDisplayNameDriver(DriverInterface $driver)
    {
        static::$displayNameDriver = $driver;
    }

    public static function setPasswordCheckers(array $checkers)
    {
        static::$passwordCheckers = $checkers;
    }

    /**
     * 重命名用户
     *
     * @param string $username
     * @return $this
     */
    public function rename($username)
    {
        if ($username !== $this->username) {
            $oldUsername = $this->username;
            $this->username = $username;

            $this->raise(new Renamed($this, $oldUsername));
        }

        return $this;
    }

    /**
     * 更改用户的电子邮件
     *
     * @param string $email
     * @return $this
     */
    public function changeEmail($email)
    {
        if ($email !== $this->email) {
            $this->email = $email;

            $this->raise(new EmailChanged($this));
        }

        return $this;
    }

    /**
     * 请求更改用户的电子邮件
     *
     * @param string $email
     * @return $this
     */
    public function requestEmailChange($email)
    {
        if ($email !== $this->email) {
            $this->raise(new EmailChangeRequested($this, $email));
        }

        return $this;
    }

    /**
     * 更改用户密码
     *
     * @param string $password
     * @return $this
     */
    public function changePassword($password)
    {
        $this->password = $password;

        $this->raise(new PasswordChanged($this));

        return $this;
    }

    /**
     * 设置 password 属性，将其存储为哈希值
     *
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? static::$hasher->make($value) : '';
    }

    /**
     * 将所有讨论标记为已读
     *
     * @return $this
     */
    public function markAllAsRead()
    {
        $this->marked_all_as_read_at = Carbon::now();

        return $this;
    }

    /**
     * 将所有通知标记为已读
     *
     * @return $this
     */
    public function markNotificationsAsRead()
    {
        $this->read_notifications_at = Carbon::now();

        return $this;
    }

    /**
     * 更改用户头像的路径
     *
     * @param string|null $path
     * @return $this
     */
    public function changeAvatarPath($path)
    {
        $this->avatar_url = $path;

        $this->raise(new AvatarChanged($this));

        return $this;
    }

    /**
     * 获取用户头像的 URL
     *
     * @param string|null $value
     * @return string
     */
    public function getAvatarUrlAttribute(string $value = null)
    {
        if ($value && strpos($value, '://') === false) {
            return resolve(Factory::class)->disk('cmf-avatars')->url($value);
        }

        return $value;
    }

    /**
     * 获取用户的显示名称
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return static::$displayNameDriver->displayName($this);
    }

    /**
     * 检查给定的密码是否与用户的密码匹配
     *
     * @param string $password
     * @return bool
     */
    public function checkPassword(string $password)
    {
        $valid = false;

        foreach (static::$passwordCheckers as $checker) {
            $result = $checker($this, $password);

            if ($result === false) {
                return false;
            } elseif ($result === true) {
                $valid = true;
            }
        }

        return $valid;
    }

    /**
     * 激活用户帐户
     *
     * @return $this
     */
    public function activate()
    {
        if (! $this->is_email_confirmed) {
            $this->is_email_confirmed = true;

            $this->raise(new Activated($this));
        }

        return $this;
    }

    /**
     * 根据用户组检查用户是否具有特定权限
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->getPermissions());
    }

    /**
     * 根据用户的组检查用户是否具有与给定字符串类似的权限
     *
     * @param string $match
     * @return bool
     */
    public function hasPermissionLike($match)
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($this->getPermissions() as $permission) {
            if (substr($permission, -strlen($match)) === $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据此用户的首选项，获取应向此用户发出警报的通知类型
     *
     * @return array
     */
    public function getAlertableNotificationTypes()
    {
        $types = array_keys(Notification::getSubjectModels());

        return array_filter($types, [$this, 'shouldAlert']);
    }

    /**
     * 获取用户的未读通知数
     *
     * @return int
     */
    public function getUnreadNotificationCount()
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * 返回尚未读取的所有通知的查询生成器
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function unreadNotifications()
    {
        return $this->notifications()
            ->whereIn('type', $this->getAlertableNotificationTypes())
            ->whereNull('read_at')
            ->where('is_deleted', false)
            ->whereSubjectVisibleTo($this);
    }

    /**
     * 获取所有尚未阅读的通知
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getUnreadNotifications()
    {
        return $this->unreadNotifications()->get();
    }

    /**
     * 获取用户未见过的新通知的数量
     *
     * @return int
     */
    public function getNewNotificationCount()
    {
        return $this->unreadNotifications()
            ->where('created_at', '>', $this->read_notifications_at ?? 0)
            ->count();
    }

    /**
     * 通过转换此用户存储的首选项并将其与默认值合并，获取此用户的所有已注册首选项的值
     *
     * @param string|null $value
     * @return array
     */
    public function getPreferencesAttribute($value)
    {
        $defaults = array_map(function ($value) {
            return $value['default'];
        }, static::$preferences);

        $user = $value !== null ? Arr::only((array) json_decode($value, true), array_keys(static::$preferences)) : [];

        return array_merge($defaults, $user);
    }

    /**
     * 对数据库中存储的首选项数组进行编码
     *
     * @param mixed $value
     */
    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = json_encode($value);
    }

    /**
     * 检查用户是否应收到通知类型的警报
     *
     * @param string $type
     * @return bool
     */
    public function shouldAlert($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'alert'));
    }

    /**
     * 检查用户是否应收到通知类型的电子邮件
     *
     * @param string $type
     * @return bool
     */
    public function shouldEmail($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'email'));
    }

    /**
     * 获取此用户的首选项的值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference($key, $default = null)
    {
        return Arr::get($this->preferences, $key, $default);
    }

    /**
     * 设置此用户的首选项的值
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setPreference($key, $value)
    {
        if (isset(static::$preferences[$key])) {
            $preferences = $this->preferences;

            if (! is_null($transformer = static::$preferences[$key]['transformer'])) {
                $preferences[$key] = call_user_func($transformer, $value);
            } else {
                $preferences[$key] = $value;
            }

            $this->preferences = $preferences;
        }

        return $this;
    }

    /**
     * 将用户设置为刚才最后一次看到
     *
     * @return $this
     */
    public function updateLastSeen()
    {
        $now = Carbon::now();

        if ($this->last_seen_at === null || $this->last_seen_at->diffInSeconds($now) > User::LAST_SEEN_UPDATE_DIFF) {
            $this->last_seen_at = $now;
        }

        return $this;
    }

    /**
     * 检查用户是否为管理员
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->groups->contains(Group::ADMINISTRATOR_ID);
    }

    /**
     * 检查用户是否为访客
     *
     * @return bool
     */
    public function isGuest()
    {
        return false;
    }

    /**
     * 确保允许当前用户执行某些操作
     *
     * 如果不满足条件，将引发异常，表示缺少权限。这是关于*授权*的，即在不更改权限（或使用其他用户帐户）的情况下重试此类请求/操作是没有意义的
     *
     * @param bool $condition
     * @throws PermissionDeniedException
     */
    public function assertPermission($condition)
    {
        if (! $condition) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * 确保给定的参与者已通过身份验证
     *
     * 这将为来宾用户引发异常，表明 *授权* 失败。因此，他们可以在登录（或使用其他身份验证方式）后重试该操作
     *
     * @throws NotAuthenticatedException
     */
    public function assertRegistered()
    {
        if ($this->isGuest()) {
            throw new NotAuthenticatedException;
        }
    }

    /**
     * @param string $ability
     * @param mixed $arguments
     * @throws PermissionDeniedException
     */
    public function assertCan($ability, $arguments = null)
    {
        $this->assertPermission(
            $this->can($ability, $arguments)
        );
    }

    /**
     * @throws PermissionDeniedException
     */
    public function assertAdmin()
    {
        $this->assertCan('administrate');
    }

    /**
     * 定义与用户帖子的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * 定义与用户讨论的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * 定义与用户阅读讨论的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Discussion>
     */
    public function read()
    {
        return $this->belongsToMany(Discussion::class);
    }

    /**
     * 定义与用户组的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function visibleGroups()
    {
        return $this->belongsToMany(Group::class)->where('is_hidden', false);
    }

    /**
     * 定义与用户通知的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * 定义与用户电子邮件令牌的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailTokens()
    {
        return $this->hasMany(EmailToken::class);
    }

    /**
     * 定义与用户电子邮件令牌的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function passwordTokens()
    {
        return $this->hasMany(PasswordToken::class);
    }

    /**
     * 定义与用户所在所有组的权限的关系
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function permissions()
    {
        $groupIds = [Group::GUEST_ID];

        // 如果用户的帐户尚未激活，则他们基本上只不过是访客。如果他们被激活，我们可以为他们提供标准的 'member' 组，以及他们被分配到的任何其他组。
        if ($this->is_email_confirmed) {
            $groupIds = array_merge($groupIds, [Group::MEMBER_ID], $this->groups->pluck('id')->all());
        }

        foreach (static::$groupProcessors as $processor) {
            $groupIds = $processor($this, $groupIds);
        }

        return Permission::whereIn('group_id', $groupIds);
    }

    /**
     * 获取用户拥有的权限列表
     *
     * @return string[]
     */
    public function getPermissions()
    {
        if (is_null($this->permissions)) {
            $this->permissions = $this->permissions()->pluck('permission')->all();
        }

        return $this->permissions;
    }

    /**
     * 定义与用户访问令牌的关系
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    /**
     * 获取用户的登录提供程序
     */
    public function loginProviders()
    {
        return $this->hasMany(LoginProvider::class);
    }

    /**
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function can($ability, $arguments = null)
    {
        return static::$gate->allows($this, $ability, $arguments);
    }

    /**
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = null)
    {
        return ! $this->can($ability, $arguments);
    }

    /**
     * 设置用于哈希处理密码的哈希程序
     *
     * @param Hasher $hasher
     *
     * @internal
     */
    public static function setHasher(Hasher $hasher)
    {
        static::$hasher = $hasher;
    }

    /**
     * 使用转换器和默认值注册首选项
     *
     * @param string $key
     * @param callable $transformer
     * @param mixed $default
     *
     * @internal
     */
    public static function registerPreference($key, callable $transformer = null, $default = null)
    {
        static::$preferences[$key] = compact('transformer', 'default');
    }

    /**
     * 注册处理用户组列表的回调
     *
     * @param callable $callback
     * @return void
     *
     * @internal
     */
    public static function addGroupProcessor($callback)
    {
        static::$groupProcessors[] = $callback;
    }

    /**
     * 获取首选项的密钥，该首选项标记用户是否会通过 $method 接收 $type 通知
     *
     * @param string $type
     * @param string $method
     * @return string
     */
    public static function getNotificationPreferenceKey($type, $method)
    {
        return 'notify_'.$type.'_'.$method;
    }

    /**
     * 刷新用户的评论计数
     *
     * @return $this
     */
    public function refreshCommentCount()
    {
        $this->comment_count = $this->posts()
            ->where('type', 'comment')
            ->where('is_private', false)
            ->count();

        return $this;
    }

    /**
     * 刷新用户的评论计数
     *
     * @return $this
     */
    public function refreshDiscussionCount()
    {
        $this->discussion_count = $this->discussions()
            ->where('is_private', false)
            ->count();

        return $this;
    }
}
