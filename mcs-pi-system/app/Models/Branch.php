<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $primaryKey = 'branch_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['branch_id', 'branch', 'faculty', 'hos'];

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id', 'branch_id');
    }

    public function getRouteKeyName()
    {
        return 'branch_id';
    }

}
