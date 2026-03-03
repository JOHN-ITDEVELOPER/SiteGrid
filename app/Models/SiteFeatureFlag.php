<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteFeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'flag_name',
        'value',
        'rollout_percent',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
