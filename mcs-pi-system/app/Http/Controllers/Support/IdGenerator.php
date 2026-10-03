<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class IdGenerator
{
    public static function next(string $table, string $column, int $start, int $end): int
    {
        $max = DB::table($table)
            ->whereBetween($column, [$start, $end])
            ->max($column);

        if ($max === null) {
            return $start;
        }
        if ((int) $max >= $end) {
            throw new \RuntimeException("No free IDs left in range {$start}-{$end} for {$table}.{$column}");
        }
        return (int) $max + 1;
    }
}
