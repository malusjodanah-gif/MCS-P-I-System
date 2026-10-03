<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'item_id', 'item', 'uom', 'category', 'sub_category', 'supplier_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function itemBranches()
    {
        return $this->hasMany(ItemBranch::class, 'item_id', 'item_id');
    }

    public function getRouteKeyName()
    {
        return 'item_id';
    }

}
