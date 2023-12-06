<?php

namespace Cmf\Install\Steps;

use Cmf\Database\DatabaseMigrationRepository;
use Cmf\Database\Migrator;
use Cmf\Install\Step;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\Filesystem;

class RunMigrations implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var string
     */
    private $path;

    public function __construct(ConnectionInterface $database, $path)
    {
        $this->database = $database;
        $this->path = $path;
    }

    public function getMessage()
    {
        return 'Running migrations';
    }

    public function run()
    {
        $migrator = $this->getMigrator();

        $migrator->installFromSchema($this->path);
        $migrator->run($this->path);
    }

    private function getMigrator()
    {
        $repository = new DatabaseMigrationRepository(
            $this->database,
            'migrations'
        );
        $files = new Filesystem;

        return new Migrator($repository, $this->database, $files);
    }
}
