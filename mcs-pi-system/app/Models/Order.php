<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $primaryKey = 'order_line_id';

    protected $fillable = [
        'ge_number', 'inventory_flag', 'po_number', 'supplier_id', 'order_date',
        'user_id', 'branch_id', 'account_code', 'item_id', 'item',
        'uom', 'quantity', 'unit_cost', 'total_cost', 'status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public const STATUSES = [
        'Pending', 'Approved', 'Rejected', 'Cancelled',
        'Ordered', 'Received', 'Backorder', 'Complete',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** FK ItemID → ITEM table (column "item" is the line description per spec) */
    public function stockItem()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'order_line_id', 'order_line_id');
    }
}
