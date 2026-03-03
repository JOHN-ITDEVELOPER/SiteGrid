<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\InventoryItem;

class InventoryStock extends Model
{
    protected $fillable = [
        'site_id',
        'item_id',
        'quantity',
        'low_stock_threshold',
        'avg_unit_cost',
    ];

    protected $casts = [
        'quantity' => 'float',
        'low_stock_threshold' => 'float',
        'avg_unit_cost' => 'float',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
