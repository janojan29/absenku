<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_meters',
        'check_in_start_time',
        'check_in_end_time',
        'late_tolerance_minutes',
        'check_out_start_time',
        'check_out_end_time',
        'is_attendance_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'check_in_start_time' => 'string',
        'check_in_end_time' => 'string',
        'late_tolerance_minutes' => 'integer',
        'check_out_start_time' => 'string',
        'check_out_end_time' => 'string',
        'is_attendance_active' => 'boolean',
    ];

    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => config('app.name', 'Sekolah'),
                'latitude' => -6.2000000,
                'longitude' => 106.8166667,
                'radius_meters' => 50,
                'check_in_start_time' => '07:00:00',
                'check_in_end_time' => '08:00:00',
                'late_tolerance_minutes' => 15,
                'check_out_start_time' => '15:00:00',
                'check_out_end_time' => '17:00:00',
                'is_attendance_active' => true,
            ]
        );
    }
}
