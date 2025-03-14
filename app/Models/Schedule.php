<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'status',
        'price',
        'booking_id',
        'service_id'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
