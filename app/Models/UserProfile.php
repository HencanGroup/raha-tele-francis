<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'gender',
        'searching_for',
        'birth_date',
        'age',
        'bio',
        'profile_picture',
        'gallery',
        'county_id',
        'town_id',
        'location',
        'latitude',
        'longitude',
        'preferences',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'gallery' => 'array',
        'preferences' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function town()
    {
        return $this->belongsTo(Town::class);
    }

    // Calculate age automatically if not provided
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->birth_date && !$model->age) {
                $model->age = now()->diffInYears($model->birth_date);
            }
        });
    }
}