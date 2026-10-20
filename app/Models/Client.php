<?php

namespace App\Models;

use App\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, BelongsToAgency;

    protected $fillable = [
        'agency_id',
        'user_id',
        'assigned_agent_id',
        'name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function interactions()
    {
        return $this->hasMany(ClientInteraction::class);
    }
}
