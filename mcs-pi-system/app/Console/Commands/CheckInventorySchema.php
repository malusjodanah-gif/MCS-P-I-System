<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckInventorySchema extends Command
{
    protected $signature = 'mcs:check-schema';

    protected $description = 'Check required receipt reversal columns and reject obsolete revision columns without changing data';

    public function handle(): int
    {
        $missing = [];
        foreach (['item_branches', 'receipts'] as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = "$table (table)";
            }
        }
        foreach ([
            'receipts' => ['stock_before', 'cost_before', 'purchase_unit_cost', 'stock_after', 'cost_after'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    $missing[] = "$table.$column";
                }
            }
        }
        $obsolete = [];
        foreach ([
            'item_branches' => ['stock_revision'],
            'receipts' => ['stock_revision_after'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    $obsolete[] = "$table.$column";
                }
            }
        }
        if ($obsolete) {
            $this->error('Obsolete specification-mismatched columns remain: '.implode(', ', $obsolete));
            $this->line('Back up the database, then run php artisan migrate --force and rerun this check.');

            return self::FAILURE;
        }
        if ($missing) {
            $this->error('Receipt reversal schema is incomplete: '.implode(', ', $missing));
            $this->line('Back up the database, then run php artisan migrate --force and rerun this check.');

            return self::FAILURE;
        }
        $this->info('Receipt reversal schema is ready and obsolete revision columns are absent.');

        return self::SUCCESS;
    }
}
