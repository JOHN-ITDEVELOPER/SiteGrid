<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\InventoryEvidence;

class ProcurementRequest extends Model
{
    protected $fillable = [
        'site_id',
        'reference',
        'status',
        'purpose',
        'supplier_name',
        'po_number',
        'expected_delivery_date',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'delivered_at',
        'requested_by',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequestItem::class, 'procurement_request_id');
    }

    public function evidences(): MorphMany
    {
        return $this->morphMany(InventoryEvidence::class, 'evidenceable');
    }
}
