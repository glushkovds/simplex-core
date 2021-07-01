<?php

namespace Simplex\Core\DB;

use Simplex\Core\Alert\Console\Alert;
use Simplex\Core\ConsoleBase;
use Simplex\Core\DB;

class Migrator extends ConsoleBase
{
    protected $migrationFiles = [];

    public function __construct()
    {
        // populate migration files
        foreach (scandir('database/migrations') as $file) {
            $path = pathinfo($file);
            if ($path['extension'] ?? '' == 'php') {
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

    /**
     * @param int|string $steps
     * @throws \Exception
     */
    public function up($steps = 'all')
    {
        // find migrations that were not processed yet
        $list = (new AQ())
            ->from('migration')
            ->asArray()
            ->select('*')
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
            /** @var \Simplex\Core\DB\Migration $class */
            $class = include $this->migrationFiles[$migration]['file'];
            if (!$class->up() || !DB::query('INSERT INTO `migration` (`file`) VALUES (?)', [$migration])) {
                Alert::error('Failed to up migration ' . $migration);
                continue;
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
        $list = (new AQ())
            ->from('migration')
            ->select('*')
            ->orderBy('id DESC')
            ->asArray();

        if ($steps !== 'all')
            $list->limit($steps);

        $list = $list->all();

        foreach ($list as $migration) {
            // run migration
            /** @var \Simplex\Core\DB\Migration $class */
            $class = include $this->migrationFiles[$migration['file']]['file'];
            if (!$class->down() || !DB::query('DELETE FROM `migration` WHERE `id` = ?', [$migration['id']])) {
                Alert::error('Failed to down migration ' . $migration['file']);
                continue;
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