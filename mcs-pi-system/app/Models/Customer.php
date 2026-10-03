<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $primaryKey = 'customer_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['customer_id', 'customer', 'customer_type', 'email'];

    public function getRouteKeyName()
    {
        return 'customer_id';
    }

}
