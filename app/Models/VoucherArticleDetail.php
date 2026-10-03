<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherArticleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'freezing_fish_id',
        'container_id',
        'fish_warehouse_id',
        'quantity',
        'calculated_boxes',
        'unit_price',
        'total_price',
        'fish_stock_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'calculated_boxes' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function freezingFish(): BelongsTo
    {
        return $this->belongsTo(FreezingFish::class, 'freezing_fish_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class, 'container_id');
    }

    public function fishWarehouse(): BelongsTo
    {
        return $this->belongsTo(FishWarehouse::class, 'fish_warehouse_id');
    }

    public function fishStock(): BelongsTo
    {
        return $this->belongsTo(FishStock::class, 'fish_stock_id');
    }

    public function consumableDetails(): HasMany
    {
        return $this->hasMany(ArticleConsumableDetail::class, 'voucher_article_detail_id');
    }
}
