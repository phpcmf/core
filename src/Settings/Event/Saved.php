<?php

namespace Cmf\Settings\Event;

class Saved
{
    /**
     * @var array
     */
    public $settings;

    /**
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }
}
