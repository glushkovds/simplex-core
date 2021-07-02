<?php

namespace Simplex\Core\DB;

use ReflectionClass;
use Simplex\Core\Alert\Console\Alert;
use Simplex\Core\ConsoleBase;
use Simplex\Core\DB;
use Simplex\Core\DB\Migration as MigrationInterface;
use Simplex\Core\Models\Migration as Migration;

class Migrator extends ConsoleBase
{
    protected $migrationFiles = [];

    public function __construct()
    {
        // populate migration files
        foreach (scandir('database/migrations') as $file) {
            $path = pathinfo($file);
            if (($path['extension'] ?? '') == 'php') {
                $this->migrationFiles[$path['filename']] = [
                    'file' => 'database/migrations/' . $file,
                    'name' => $path['filename']
                ];
            }
        }
    }

    protected function getNewMigrations(array $list): array
    {
        $files = [];
        foreach ($this->migrationFiles as $file) {
            $files[] = $file['name'];
        }

        if (empty($list)) {
            return $files;
        }

        $migrations = [];
        foreach ($list as $item) {
            $migrations[] = $item['file'];
        }

        return array_diff($files, $migrations);
    }

    protected function getMigrationObject(string $name): ?MigrationInterface
    {
        try {
            $class = include $this->migrationFiles[$name]['file'];

            $reflection = new ReflectionClass($class);
            if (!$reflection->implementsInterface(MigrationInterface::class)) {
                Alert::error('Migration ' . $name . ' should implement Migration interface');
                return null;
            }

            return $class;
        } catch (\Throwable $ex) {
            Alert::error('FATAL: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * @param int|string $steps
     * @throws \Exception
     */
    public function up($steps = 'all')
    {
        // find migrations that were not processed yet
        $list = Migration::findAdv()
            ->asArray()
            ->all();

        $migrations = $this->getNewMigrations($list);
        if (empty($migrations)) {
            Alert::success('No new migrations');
            return;
        }

        // run migrations
        $counter = 0;
        foreach ($migrations as $migration) {
            if ($steps != 'all') {
                if ($counter++ >= (int)$steps) {
                    break;
                }
            }

            // run and remember the migration
            $class = $this->getMigrationObject($migration);
            if (!$class) {
                return;
            }

            if (!$class->up()) {
                Alert::error('Failed to up migration ' . $migration);
                return;
            }

            $dbMigration = new Migration();
            if (!$dbMigration->insert(['file' => $migration])) {
                if (!$class->down()) {
                    Alert::error('FATAL: failed to down migration ' . $migration . ' after DB failure');
                    return;
                }

                Alert::error('Failed to remember migration ' . $migration);
                return;
            }

            Alert::text('Migration ' . $migration . ' is up!');
        }

        Alert::success('Up is done!');
    }

    /**
     * @param int|string $steps
     * @throws \Exception
     */
    public function down($steps = 1)
    {
        $list = Migration::findAdv()
            ->orderBy('`id` DESC');

        if ($steps != 'all') {
            $list->limit((int)$steps);
        }

        $list = $list->all();

        /** @var Migration $migration */
        foreach ($list as $migration) {
            $class = $this->getMigrationObject($migration->file);
            if (!$class) {
                return;
            }

            if (!$class->down()) {
                Alert::error('Failed to down migration ' . $migration['file']);
                return;
            }

            if (!$migration->delete()) {
                if (!$class->up()) {
                    Alert::error('FATAL: Failed to up migration ' . $migration['file'] . 'after DB failure');
                    return;
                }

                Alert::error('Failed to forget migration ' . $migration['file']);
                return;
            }

            Alert::text('Migration ' . $migration['file'] . ' is down!');
        }

        Alert::success('Down is done!');
    }

    /**
     * @param int|string $steps
     * @throws \Exception
     */
    public function refresh($steps = 1)
    {
        $this->down($steps);
        $this->up($steps);
    }
}