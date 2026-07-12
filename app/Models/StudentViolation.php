<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentViolation extends Model
{
    protected $fillable = [
        'student_profile_id',
        'reported_by',
        'subject',
        'incident_time',
        'description',
    ];

    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
