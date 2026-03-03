<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\InventoryEvidence;

class SiteProgressLog extends Model
{
    protected $fillable = [
        'site_id',
        'log_date',
        'sector',
        'title',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evidences(): MorphMany
    {
        return $this->morphMany(InventoryEvidence::class, 'evidenceable');
    }
}
