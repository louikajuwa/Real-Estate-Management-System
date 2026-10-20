<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Traits\BelongsToAgency;

class Property extends Model
{
    use HasFactory, SoftDeletes, BelongsToAgency;

    protected $fillable = [
        'agency_id',
        'agent_id',
        'title',
        'slug',
        'description',
        'type',
        'price',
        'address',
        'city',
        'bedrooms',
        'bathrooms',
        'area_sqft',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'status'      => PropertyStatus::class,
        'type'        => PropertyType::class,
        'price'       => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
