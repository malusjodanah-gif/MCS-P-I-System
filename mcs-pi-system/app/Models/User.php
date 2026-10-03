<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'name', 'email', 'password', 'branch_id', 'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public const ROLES = [
        'Administrator',
        'Purchasing Officer',
        'Inventory Officer',
        'EndUser',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Administrator';
    }

    public function canWritePurchasing(): bool
    {
        return in_array($this->role, ['Administrator', 'Purchasing Officer'], true);
    }

    public function canWriteInventory(): bool
    {
        return in_array($this->role, ['Administrator', 'Inventory Officer'], true);
    }

    public function getAuthIdentifierName()
    {
        return 'user_id';
    }

    public function isViewOnly(): bool
    {
        return $this->role === 'EndUser';
    }

    public function getRouteKeyName()
    {
        return 'user_id';
    }

}
