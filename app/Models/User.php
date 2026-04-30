<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_OWNER  = 'owner';
    public const ROLE_DEALER = 'dealer';
    public const ROLE_CLIENT = 'client';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'created_by',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isOwner(): bool  { return $this->role === self::ROLE_OWNER; }
    public function isDealer(): bool { return $this->role === self::ROLE_DEALER; }
    public function isClient(): bool { return $this->role === self::ROLE_CLIENT; }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(self::class, 'created_by')->where('role', self::ROLE_CLIENT);
    }

    public function ordersAsDealer(): HasMany
    {
        return $this->hasMany(Order::class, 'dealer_id');
    }

    public function ordersAsClient(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }
}
