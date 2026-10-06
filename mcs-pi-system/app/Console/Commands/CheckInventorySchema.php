<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckInventorySchema extends Command
{
    protected $signature = 'mcs:check-schema';
    protected $description = 'Check required inventory revision and receipt reversal columns without changing data';

    public function handle(): int
    {
        $missing = [];
        foreach ([
            'item_branches' => ['stock_revision'],
            'receipts' => ['stock_before', 'cost_before', 'purchase_unit_cost', 'stock_revision_after'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    $missing[] = "$table.$column";
                }
            }
        }
        if ($missing) {
            $this->error('Inventory schema is incomplete: '.implode(', ', $missing));
            $this->line('Back up the database, then run php artisan migrate --force and rerun this check.');
            return self::FAILURE;
        }
        $this->info('Inventory revision and receipt reversal schema is ready.');
        return self::SUCCESS;
    }
}
