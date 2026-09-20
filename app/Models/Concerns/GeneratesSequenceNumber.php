<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Next number for an auto-generated code column (e.g. DRG-0007), continuing
 * from the highest suffix already issued under that prefix — including
 * soft-deleted rows — so deleting a record never causes a reused number.
 */
trait GeneratesSequenceNumber
{
    protected static function nextSequenceNumber(string $column, string $prefix, int $pad = 4): string
    {
        $query = in_array(SoftDeletes::class, class_uses_recursive(static::class))
            ? static::withTrashed()
            : static::query();

        $last = $query->where($column, 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING({$column}, ?) AS UNSIGNED)) AS n", [strlen($prefix) + 1])
            ->value('n');

        return $prefix . str_pad(((int) $last) + 1, $pad, '0', STR_PAD_LEFT);
    }
}
