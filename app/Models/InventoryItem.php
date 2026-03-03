<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InventoryCategory;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Site;

class InventoryItem extends Model
{
    protected $fillable = [
        'site_id',
        'category_id',
        'name',
        'sku',
        'unit',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'item_id');
    }
}
