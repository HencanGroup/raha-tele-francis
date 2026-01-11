<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class County extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
    ];

    public function towns()
    {
        return $this->hasMany(Town::class);
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