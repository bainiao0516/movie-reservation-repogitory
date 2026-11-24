<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'duration_minutes',
        'thumbnail_path',
        'rating',
        'director',
        'cast',
        'description',
        'release_date',
        'end_date',
    ];

    /**
     * 属性の型キャスト定義
     *
     * @var array<string, string>
     */
    protected $casts = [
        'release_date' => 'date',
        'end_date' => 'date',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
