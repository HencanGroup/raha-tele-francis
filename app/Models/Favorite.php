<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Favorite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'escort_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function escort()
    {
        return $this->belongsTo(Escort::class);
    }

    // Check if escort is favorited by user
    public static function isFavorited($userId, $escortId)
    {
        return self::where('user_id', $userId)
            ->where('escort_id', $escortId)
            ->exists();
    }

    // Toggle favorite status
    public static function toggle($userId, $escortId)
    {
        $favorite = self::where('user_id', $userId)
            ->where('escort_id', $escortId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false;
        } else {
            self::create([
                'user_id' => $userId,
                'escort_id' => $escortId,
            ]);
            return true;
        }
    }
}