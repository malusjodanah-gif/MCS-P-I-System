<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $primaryKey = 'supplier_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'supplier_id', 'supplier', 'address', 'contact', 'payment_term', 'currency',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'supplier_id', 'supplier_id');
    }

    public function getRouteKeyName()
    {
        return 'supplier_id';
    }

}
