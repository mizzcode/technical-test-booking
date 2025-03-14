<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'transaction_id',
        'payment_details',
    ];

    public function schedule() {
        return $this->hasOne(Schedule::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}