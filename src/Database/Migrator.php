<?php

namespace Cmf\Database;

use Cmf\Database\Exception\MigrationKeyMissing;
use Cmf\Extension\Extension;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class Migrator
{
    /**
     * 迁移存储库实现
     *
     * @var \Cmf\Database\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * 输出接口实现
     *
     * @var OutputInterface|null
     */
    protected $output;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * 创建新的迁移器实例
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        ConnectionInterface $connection,
        Filesystem $files
    ) {
        $this->files = $files;
        $this->repository = $repository;

        if (! ($connection instanceof MySqlConnection)) {
            throw new InvalidArgumentException('Only MySQL connections are supported');
        }

        $this->connection = $connection;

        $connection->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * 在给定路径上运行未完成的迁移
     *
     * @param  string    $path
     * @param  Extension|null $extension
     * @return void
     */
    public function run($path, Extension $extension = null)
    {
        $files = $this->getMigrationFiles($path);

        $ran = $this->repository->getRan($extension ? $extension->getId() : null);

        $migrations = array_diff($files, $ran);

        $this->runMigrationList($path, $migrations, $extension);
    }

    /**
     * 运行一系列迁移
     *
     * @param  string    $path
     * @param  array     $migrations
     * @param  Extension|null $extension
     * @return void
     */
    public function runMigrationList($path, $migrations, Extension $extension = null)
    {
        // 首先，我们将确保有任何要运行的迁移。如果没有，我们将向开发人员记下它，以便他们知道所有迁移都是针对此数据库系统运行的
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // 一旦我们有了迁移数组，我们将旋转它们并 "up" 运行迁移，以便对数据库进行更改。然后，我们将记录迁移已运行，以便下次执行时不会重复
        foreach ($migrations as $file) {
            $this->runUp($path, $file, $extension);
        }
    }

    /**
     * 运行 "up" 迁移实例
     *
     * @param  string    $path
     * @param  string    $file
     * @param  Extension|null $extension
     * @return void
     */
    protected function runUp($path, $file, Extension $extension = null)
    {
        $this->resolveAndRunClosureMigration($path, $file);

        // 一旦我们运行了一个迁移类，我们将记录它在这个存储库中运行，这样我们下次在应用程序中进行迁移时就不会尝试运行它。迁移存储库保持迁移顺序
        $this->repository->log($file, $extension ? $extension->getId() : null);

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * 回滚所有当前应用的迁移
     *
     * @param  string    $path
     * @param  Extension|null $extension
     * @return int
     */
    public function reset($path, Extension $extension = null)
    {
        $migrations = array_reverse($this->repository->getRan(
            $extension ? $extension->getId() : null
        ));

        $count = count($migrations);

        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            foreach ($migrations as $migration) {
                $this->runDown($path, $migration, $extension);
            }
        }

        return $count;
    }

    /**
     * 运行 "down" 迁移实例
     *
     * @param  string    $path
     * @param  string    $file
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    protected function runDown($path, $file, Extension $extension = null)
    {
        $this->resolveAndRunClosureMigration($path, $file, 'down');

        // 一旦我们成功 "down" 了迁移，我们将将其从迁移存储库中删除，因此它将被视为应用程序尚未运行，然后可以通过任何后续操作触发
        $this->repository->delete($file, $extension ? $extension->getId() : null);

        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * 根据迁移方向运行闭包迁移
     *
     * @param        $migration
     * @param string $direction
     * @throws MigrationKeyMissing
     */
    protected function runClosureMigration($migration, $direction = 'up')
    {
        if (is_array($migration) && array_key_exists($direction, $migration)) {
            call_user_func($migration[$direction], $this->connection->getSchemaBuilder());
        } else {
            throw new MigrationKeyMissing($direction);
        }
    }

    /**
     * 解析并运行迁移，并根据需要将文件名分配给异常
     *
     * @param string $path
     * @param string $file
     * @param string $direction
     * @throws MigrationKeyMissing
     */
    protected function resolveAndRunClosureMigration(string $path, string $file, string $direction = 'up')
    {
        $migration = $this->resolve($path, $file);

        try {
            $this->runClosureMigration($migration, $direction);
        } catch (MigrationKeyMissing $exception) {
            throw $exception->withFile("$path/$file.php");
        }
    }

    /**
     * 获取给定路径中的所有迁移文件
     *
     * @param  string $path
     * @return array
     */
    public function getMigrationFiles($path)
    {
        $files = $this->files->glob($path.'/*_*.php');

        if ($files === false) {
            return [];
        }

        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

        // 一旦我们获得了所有格式化的文件名，我们将对它们进行排序，并且由于它们都以时间戳开头，因此这应该按照应用程序开发人员实际创建的顺序为我们提供迁移
        sort($files);

        return $files;
    }

    /**
     * 从文件解析迁移实例
     *
     * @param  string $path
     * @param  string $file
     * @return array
     */
    public function resolve($path, $file)
    {
        $migration = "$path/$file.php";

        if ($this->files->exists($migration)) {
            return $this->files->getRequire($migration);
        }

        return [];
    }

    /**
     * 从模式转储初始化 PHPCmf 数据库。
     *
     * @param string $path 到包含转储的目录
     */
    public function installFromSchema(string $path)
    {
        $schemaPath = "$path/install.dump";

        $startTime = microtime(true);

        $dump = file_get_contents($schemaPath);

        $this->connection->getSchemaBuilder()->disableForeignKeyConstraints();

        foreach (explode(';', $dump) as $statement) {
            $statement = trim($statement);

            if (empty($statement) || substr($statement, 0, 2) === '/*') {
                continue;
            }

            $statement = str_replace(
                'db_prefix_',
                $this->connection->getTablePrefix(),
                $statement
            );
            $this->connection->statement($statement);
        }

        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);
        $this->note('<info>Loaded stored database schema.</info> ('.$runTime.'ms)');
    }

    /**
     * 设置控制台应使用的输出实现
     *
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * 在 conosle 的输出中写下注释
     *
     * @param string $message
     * @return void
     */
    protected function note($message)
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }

    /**
     * 确定迁移存储库是否存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }
}
