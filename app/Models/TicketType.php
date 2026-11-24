<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scheduleSeats(): HasMany
    {
        return $this->hasMany(ScheduleSeat::class);
    }
}
