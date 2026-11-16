<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * The search queries use MySQL's REGEXP_REPLACE and a multibyte-aware LOWER.
     * Teach the in-memory SQLite test database the same functions so they can be tested.
     */
    protected function registerMysqlCompatibleSqliteFunctions(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $connection->getPdo();
        $register = method_exists($pdo, 'createFunction')
            ? fn (string $name, callable $callback, int $args) => $pdo->createFunction($name, $callback, $args)
            : fn (string $name, callable $callback, int $args) => $pdo->sqliteCreateFunction($name, $callback, $args);

        $register('REGEXP_REPLACE', function (?string $subject, string $pattern, string $replacement): ?string {
            if ($subject === null) {
                return null;
            }

            // MySQL string literals unescape "\\" to "\"; SQLite keeps both characters.
            $pattern = str_replace(['\\\\', '/'], ['\\', '\\/'], $pattern);

            return preg_replace('/'.$pattern.'/u', $replacement, $subject);
        }, 3);

        $register('LOWER', fn (?string $value): ?string => $value === null ? null : mb_strtolower($value, 'UTF-8'), 1);
    }
}
