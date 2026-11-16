<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * History rows whose parent must not be deletable while they exist: table => [column, parent table].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const FOREIGN_KEYS = [
        'insurance_ledger_entries' => ['insurance_id', 'insurances'],
        'insurance_claims' => ['insurance_id', 'insurances'],
        'insurance_claim_events' => ['claim_id', 'insurance_claims'],
        'insurance_status_events' => ['insurance_id', 'insurances'],
    ];

    /**
     * Run the migrations.
     *
     * Deleting a partner used to cascade to its ledger, claims and history. Restrict instead so that
     * history can only disappear by deleting it explicitly. SQLite cannot alter foreign keys in place,
     * so this only runs on MySQL / MariaDB; it is idempotent (keys already restricted are skipped).
     */
    public function up(): void
    {
        $this->setDeleteRule('RESTRICT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setDeleteRule('CASCADE');
    }

    private function setDeleteRule(string $rule): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::FOREIGN_KEYS as $table => [$column, $parent]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $current = $this->currentForeignKey($table, $column);

            if ($current !== null && $current->delete_rule === $rule) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($current, $column, $parent, $rule) {
                if ($current !== null) {
                    $blueprint->dropForeign($current->constraint_name);
                }

                $foreign = $blueprint->foreign($column)->references('id')->on($parent);
                $rule === 'RESTRICT' ? $foreign->restrictOnDelete() : $foreign->cascadeOnDelete();
            });
        }
    }

    private function currentForeignKey(string $table, string $column): ?object
    {
        return DB::selectOne(
            'SELECT rc.CONSTRAINT_NAME AS constraint_name, rc.DELETE_RULE AS delete_rule
             FROM information_schema.REFERENTIAL_CONSTRAINTS rc
             JOIN information_schema.KEY_COLUMN_USAGE kcu
               ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
              AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
              AND kcu.TABLE_NAME = rc.TABLE_NAME
             WHERE rc.CONSTRAINT_SCHEMA = DATABASE() AND rc.TABLE_NAME = ? AND kcu.COLUMN_NAME = ?',
            [$table, $column],
        );
    }
};
