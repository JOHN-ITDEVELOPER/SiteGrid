<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSettingsAudit extends Model
{
    use HasFactory;

    protected $table = 'site_settings_audit';

    protected $fillable = [
        'site_id',
        'changed_by',
        'action',
        'change',
        'reason',
    ];

    protected $casts = [
        'change' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
