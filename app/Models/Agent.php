<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToAgency;

class Agent extends Model
{
    use HasFactory, BelongsToAgency;

    protected $fillable = [
        'agency_id',
        'user_id',
        'license_no',
        'bio',
        'phone',
        'photo_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'assigned_agent_id');
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class, 'agent_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'agent_id');
    }

    public function clientInteractions()
    {
        return $this->hasMany(ClientInteraction::class, 'agent_id');
    }
}
