<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdGenerator
{
    public static function create(string $table, string $column, int $start, int $end, ?int $requested, callable $persist): mixed
    {
        return DB::transaction(function () use ($table, $column, $start, $end, $requested, $persist) {
            // Hold the allocation lock through insertion, not just through max().
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['mcs-id:'.$table]);
            }
            if ($requested !== null && DB::table($table)->where($column, $requested)->exists()) {
                throw ValidationException::withMessages([$column => 'This number has already been allocated. Choose another number.']);
            }
            return $persist($requested ?? self::next($table, $column, $start, $end));
        }, 5);
    }

    public static function next(string $table, string $column, int $start, int $end): int
    {
        $max = DB::table($table)
            ->whereBetween($column, [$start, $end])
            ->max($column);

        if ($max === null) {
            return $start;
        }

        if ((int) $max >= $end) {
            throw ValidationException::withMessages([$column => "No IDs remain in range {$start}-{$end}."]);
        }

        return (int) $max + 1;
    }
}
