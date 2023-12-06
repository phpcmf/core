<?php

namespace Cmf\Api\Controller;

use Cmf\Foundation\Console\AssetsPublishCommand;
use Cmf\Foundation\Console\CacheClearCommand;
use Cmf\Foundation\IOException;
use Cmf\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCacheController extends AbstractDeleteController
{
    /**
     * @var CacheClearCommand
     */
    protected $command;

    /**
     * @var AssetsPublishCommand
     */
    protected $assetsPublishCommand;

    /**
     * @param CacheClearCommand $command
     */
    public function __construct(CacheClearCommand $command, AssetsPublishCommand $assetsPublishCommand)
    {
        $this->command = $command;
        $this->assetsPublishCommand = $assetsPublishCommand;
    }

    /**
     * {@inheritdoc}
     * @throws IOException|\Cmf\User\Exception\PermissionDeniedException
     */
    protected function delete(ServerRequestInterface $request)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $exitCode = $this->command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new IOException();
        }

        $exitCode = $this->assetsPublishCommand->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new IOException();
        }

        return new EmptyResponse(204);
    }
}
