<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $primaryKey = 'adjustment_number';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'adjustment_number', 'adjustment_type', 'branch_id', 'item_id',
        'quantity', 'uom', 'adjustment_date', 'purpose', 'user_id',
    ];

    protected function casts(): array
    {
        return ['adjustment_date' => 'date'];
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
