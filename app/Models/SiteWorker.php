<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'user_id',
        'role',
        'is_foreman',
        'daily_rate',
        'weekly_rate',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'is_foreman' => 'boolean',
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
