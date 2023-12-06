<?php

namespace Cmf\Database\Console;

use Cmf\Console\AbstractCommand;
use Cmf\Foundation\Paths;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;

class GenerateDumpCommand extends AbstractCommand
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Connection $connection
     * @param Paths $paths
     */
    public function __construct(Connection $connection, Paths $paths)
    {
        $this->connection = $connection;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('schema:dump')
            ->setDescription('Dump DB schema');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $dumpPath = __DIR__.'/../../../migrations/install.dump';
        /** @var Connection&MySqlConnection */
        $connection = resolve('db.connection');

        $connection
            ->getSchemaState()
            ->withMigrationTable($connection->getTablePrefix().'migrations')
            ->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            })
            ->dump($connection, $dumpPath);

        // 我们需要删除任何数据迁移，因为这些迁移不会在架构转储中捕获，并且必须单独运行。
        $coreDataMigrations = [
            '2023_11_22_000000_seed_default_groups',
            '2023_11_23_000100_seed_default_group_permissions',
        ];

        $newDump = [];
        $dump = file($dumpPath);
        foreach ($dump as $line) {
            foreach ($coreDataMigrations as $excludeMigrationId) {
                if (strpos($line, $excludeMigrationId) !== false) {
                    continue 2;
                }
            }
            $newDump[] = $line;
        }

        file_put_contents($dumpPath, implode($newDump));
    }
}
