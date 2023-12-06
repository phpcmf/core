<?php

namespace Cmf\Api\Controller;

use Cmf\Http\RequestUtil;
use Cmf\Settings\Event;
use Cmf\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetSettingsController implements RequestHandlerInterface
{
    /**
     * @var \Cmf\Settings\SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $dispatcher)
    {
        $this->settings = $settings;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $settings = $request->getParsedBody();

        $this->dispatcher->dispatch(new Event\Saving($settings));

        foreach ($settings as $k => $v) {
            $this->dispatcher->dispatch(new Event\Serializing($k, $v));

            $this->settings->set($k, $v);
        }

        $this->dispatcher->dispatch(new Event\Saved($settings));

        return new EmptyResponse(204);
    }
}
