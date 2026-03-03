<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\InventoryEvidence;
use App\Models\ProcurementRequest;

class InventoryMovement extends Model
{
    protected $fillable = [
        'site_id',
        'item_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'running_balance_after',
        'procurement_request_id',
        'reference',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'running_balance_after' => 'float',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function evidences(): MorphMany
    {
        return $this->morphMany(InventoryEvidence::class, 'evidenceable');
    }
}
