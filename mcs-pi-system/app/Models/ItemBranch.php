<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBranch extends Model
{
    protected $table = 'item_branches';
    public $incrementing = false;
    protected $primaryKey = null;

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
