<?php

namespace Cmf\Frontend;

use Cmf\Frontend\Compiler\Source\SourceCollector;
use Cmf\Locale\LocaleManager;

/**
 * @internal
 */
class AddLocaleAssets
{
    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    public function to(Assets $assets)
    {
        $assets->localeJs(function (SourceCollector $sources, string $locale) {
            foreach ($this->locales->getJsFiles($locale) as $file) {
                $sources->addFile($file);
            }
        });

        $assets->localeCss(function (SourceCollector $sources, string $locale) {
            foreach ($this->locales->getCssFiles($locale) as $file) {
                $sources->addFile($file);
            }
        });
    }
}
