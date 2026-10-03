<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $primaryKey = 'receipt_id';

    protected $fillable = [
        'po_number', 'branch_id', 'item_id', 'supplier_id', 'quantity_ordered',
        'quantity_received', 'uom', 'received_by', 'date_received', 'grn',
        'account_code', 'order_line_id',
    ];

    protected function casts(): array
    {
        return ['date_received' => 'date'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_line_id', 'order_line_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }
}
