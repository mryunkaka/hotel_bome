<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait CreatesFailingDatabaseTriggers
{
    protected function createFailingInsertTrigger(string $name, string $table, string $message): void
    {
        foreach ([$name, $table] as $identifier) {
            if (preg_match('/\A[a-zA-Z0-9_]+\z/', $identifier) !== 1) {
                throw new \InvalidArgumentException('Invalid test trigger identifier.');
            }
        }

        $message = str_replace("'", "''", $message);
        $sql = match (DB::getDriverName()) {
            'sqlite' => "CREATE TRIGGER {$name} BEFORE INSERT ON {$table} BEGIN SELECT RAISE(ABORT, '{$message}'); END",
            'mysql' => "CREATE TRIGGER {$name} BEFORE INSERT ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
            default => throw new \RuntimeException('Failing test triggers are unsupported for this database driver.'),
        };

        DB::unprepared($sql);

        if (DB::getDriverName() === 'mysql') {
            $connection = DB::connection();
            $connection->rollBack();
            $connection->beginTransaction();
        }
    }

    protected function dropTestTrigger(string $name): void
    {
        if (preg_match('/\A[a-zA-Z0-9_]+\z/', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid test trigger identifier.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::connection()->rollBack();
        }

        DB::unprepared("DROP TRIGGER IF EXISTS {$name}");
    }
}
