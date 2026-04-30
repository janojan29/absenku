<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'status',
        'late_minutes',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance_meters',
        'check_out_at',
        'check_out_late_minutes',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'check_in_latitude' => 'float',
        'check_in_longitude' => 'float',
        'check_out_latitude' => 'float',
        'check_out_longitude' => 'float',
        'check_in_distance_meters' => 'integer',
        'check_out_distance_meters' => 'integer',
        'late_minutes' => 'integer',
        'check_out_late_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
