<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Service extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description'];
    
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}