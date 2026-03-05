<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryEvidence extends Model
{
    protected $table = 'inventory_evidences';
    
    protected $fillable = [
        'site_id',
        'evidenceable_type',
        'evidenceable_id',
        'file_path',
        'caption',
        'uploaded_by',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function evidenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
