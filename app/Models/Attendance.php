<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'site_id',
        'worker_id',
        'date',
        'check_in',
        'check_out',
        'hours',
        'source',
        'is_present',
    ];

    protected $casts = [
        'date' => 'date',
        'is_present' => 'boolean',
    ];

    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
