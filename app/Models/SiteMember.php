<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'user_id',
        'role',
        'notification_preferences',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
        'assigned_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
