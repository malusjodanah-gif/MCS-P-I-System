<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBranch extends Model
{
    protected $table = 'item_branches';
    public $incrementing = false;
    protected $primaryKey = 'item_id';
    protected $keyType = 'int';

    protected static function booted(): void
    {
        static::saving(function (ItemBranch $row) {
            if ($row->exists && $row->isDirty(['current_stock', 'unit_cost'])) {
                $row->stock_revision = ((int) $row->getRawOriginal('stock_revision')) + 1;
            }
        });
    }

    // Eloquent assumes one primary key. Scope every write by both columns.
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('item_id', $this->getRawOriginal('item_id') ?? $this->item_id)
            ->where('branch_id', $this->getRawOriginal('branch_id') ?? $this->branch_id);
    }

    protected function setKeysForSelectQuery($query)
    {
        return $this->setKeysForSaveQuery($query);
    }

    protected $fillable = [
        'item_id', 'branch_id', 'uom', 'current_stock', 'unit_cost',
        'location', 'reorder_level', 'reorder_quantity',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:4',
            'current_stock' => 'integer',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }
}
