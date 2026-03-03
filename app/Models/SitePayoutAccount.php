<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitePayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'account_type',
        'provider',
        'credentials',
        'status',
        'last_tested_at',
        'created_by',
    ];

    protected $casts = [
        'credentials' => 'array',
        'last_tested_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
