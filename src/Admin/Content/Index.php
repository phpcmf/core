<?php

namespace Cmf\Admin\Content;

use Cmf\Extension\ExtensionManager;
use Cmf\Foundation\Application;
use Cmf\Frontend\Document;
use Cmf\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class Index
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Factory $view, ExtensionManager $extensions, SettingsRepositoryInterface $settings)
    {
        $this->view = $view;
        $this->extensions = $extensions;
        $this->settings = $settings;
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $extensions = $this->extensions->getExtensions();
        $extensionsEnabled = json_decode($this->settings->get('extensions_enabled', '{}'), true);
        $csrfToken = $request->getAttribute('session')->token();

        $mysqlVersion = $document->payload['mysqlVersion'];
        $phpVersion = $document->payload['phpVersion'];
        $cmfVersion = Application::VERSION;

        $document->content = $this->view->make(
            'cmf.admin::frontend.content.admin',
            compact('extensions', 'extensionsEnabled', 'csrfToken', 'cmfVersion', 'phpVersion', 'mysqlVersion')
        );

        return $document;
    }
}
