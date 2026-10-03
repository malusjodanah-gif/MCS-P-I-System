<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $primaryKey = 'issue_number';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'issue_number', 'branch_id', 'item_id', 'quantity', 'uom',
        'customer_id', 'purpose', 'user_id', 'issue_date',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date'];
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function getRouteKeyName()
    {
        return 'issue_number';
    }

}
