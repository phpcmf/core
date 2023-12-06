<?php

namespace Cmf\Extension;

use Cmf\Extension\Event\Disabling;
use Cmf\Settings\SettingsRepositoryInterface;
use Cmf\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;

class DefaultLanguagePackGuard
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Disabling $event)
    {
        if (! in_array('cmf-locale', $event->extension->extra)) {
            return;
        }

        $defaultLocale = $this->settings->get('default_locale');
        $locale = Arr::get($event->extension->extra, 'cmf-locale.code');

        if ($locale === $defaultLocale) {
            throw new PermissionDeniedException('You cannot disable the default language pack!');
        }
    }
}
