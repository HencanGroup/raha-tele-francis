<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Town extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'county_id',
    ];

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function userProfiles()
    {
        return $this->hasMany(UserProfile::class);
    }

    public function escorts()
    {
        return $this->hasMany(Escort::class);
    }
}