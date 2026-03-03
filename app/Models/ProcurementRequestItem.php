<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ProcurementRequest;

class ProcurementRequestItem extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'item_id',
        'requested_quantity',
        'approved_quantity',
        'delivered_quantity',
        'estimated_unit_cost',
        'final_unit_cost',
    ];

    protected $casts = [
        'requested_quantity' => 'float',
        'approved_quantity' => 'float',
        'delivered_quantity' => 'float',
        'estimated_unit_cost' => 'float',
        'final_unit_cost' => 'float',
    ];

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
