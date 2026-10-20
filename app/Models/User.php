<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Enums\RoleName;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password', 'phone', 'is_active', 'agency_id'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    public function agency() { return $this->belongsTo(Agency::class); }
    public function agent()  { return $this->hasOne(Agent::class); }
    public function client() { return $this->hasOne(Client::class); }

    public function isActive(): bool      { return $this->is_active; }
    public function isSuperAdmin(): bool  { return $this->hasRole(RoleName::SuperAdmin->value); }
    public function isAdmin(): bool       { return $this->hasRole(RoleName::Admin->value); }
    public function isAgent(): bool       { return $this->hasRole(RoleName::Agent->value); }
    public function isClient(): bool      { return $this->hasRole(RoleName::Client->value); }
    public function isAgencyStaff(): bool { return $this->isAdmin() || $this->isAgent(); }
}
