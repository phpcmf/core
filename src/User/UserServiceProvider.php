<?php

namespace Cmf\User;

use Cmf\Discussion\Access\DiscussionPolicy;
use Cmf\Discussion\Discussion;
use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Foundation\ContainerUtil;
use Cmf\Group\Access\GroupPolicy;
use Cmf\Group\Group;
use Cmf\Http\Access\AccessTokenPolicy;
use Cmf\Http\AccessToken;
use Cmf\Post\Access\PostPolicy;
use Cmf\Post\Post;
use Cmf\Settings\SettingsRepositoryInterface;
use Cmf\User\Access\ScopeUserVisibility;
use Cmf\User\DisplayName\DriverInterface;
use Cmf\User\DisplayName\UsernameDriver;
use Cmf\User\Event\EmailChangeRequested;
use Cmf\User\Event\Registered;
use Cmf\User\Event\Saving;
use Cmf\User\Throttler\EmailActivationThrottler;
use Cmf\User\Throttler\EmailChangeThrottler;
use Cmf\User\Throttler\PasswordResetThrottler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerDisplayNameDrivers();
        $this->registerPasswordCheckers();

        $this->container->singleton('cmf.user.group_processors', function () {
            return [];
        });

        $this->container->singleton('cmf.policies', function () {
            return [
                Access\AbstractPolicy::GLOBAL => [],
                AccessToken::class => [AccessTokenPolicy::class],
                Discussion::class => [DiscussionPolicy::class],
                Group::class => [GroupPolicy::class],
                Post::class => [PostPolicy::class],
                User::class => [Access\UserPolicy::class],
            ];
        });

        $this->container->extend('cmf.api.throttlers', function (array $throttlers, Container $container) {
            $throttlers['emailChangeTimeout'] = $container->make(EmailChangeThrottler::class);
            $throttlers['emailActivationTimeout'] = $container->make(EmailActivationThrottler::class);
            $throttlers['passwordResetTimeout'] = $container->make(PasswordResetThrottler::class);

            return $throttlers;
        });
    }

    protected function registerDisplayNameDrivers()
    {
        $this->container->singleton('cmf.user.display_name.supported_drivers', function () {
            return [
                'username' => UsernameDriver::class,
            ];
        });

        $this->container->singleton('cmf.user.display_name.driver', function (Container $container) {
            $drivers = $container->make('cmf.user.display_name.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('display_name_driver', '');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $container->make($driverClass)
                : $container->make(UsernameDriver::class);
        });

        $this->container->alias('cmf.user.display_name.driver', DriverInterface::class);
    }

    protected function registerPasswordCheckers()
    {
        $this->container->singleton('cmf.user.password_checkers', function (Container $container) {
            return [
                'standard' => function (User $user, $password) use ($container) {
                    if ($container->make('hash')->check($password, $user->password)) {
                        return true;
                    }
                }
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, Dispatcher $events)
    {
        foreach ($container->make('cmf.user.group_processors') as $callback) {
            User::addGroupProcessor(ContainerUtil::wrapCallback($callback, $container));
        }

        /**
         * @var \Illuminate\Container\Container $container
         */
        User::setHasher($container->make('hash'));
        User::setPasswordCheckers($container->make('cmf.user.password_checkers'));
        User::setGate($container->makeWith(Access\Gate::class, ['policyClasses' => $container->make('cmf.policies')]));
        User::setDisplayNameDriver($container->make('cmf.user.display_name.driver'));

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);
        $events->subscribe(TokensClearer::class);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
        User::registerPreference('locale');

        User::registerVisibilityScoper(new ScopeUserVisibility(), 'view');
    }
}
