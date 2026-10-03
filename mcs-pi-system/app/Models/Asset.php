<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $primaryKey = 'asset_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'asset_id', 'asset', 'serial_number', 'brand', 'model', 'purchase_date',
        'unit_cost', 'location', 'branch_id', 'status', 'po_number', 'item_id',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'unit_cost' => 'decimal:2',
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

    public function getRouteKeyName()
    {
        return 'asset_id';
    }

}
