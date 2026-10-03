<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedFishStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_stock_id',
        'voucher_id',
        'voucher_article_detail_id',
        'freezing_fish_id',
        'fish_warehouse_id',
        'container_id',
        'original_quantity',
        'final_quantity',
        'reception_date',
        'provider_names',
        'archived_at',
    ];

    protected $casts = [
        'reception_date' => 'date:Y-m-d',
        'original_quantity' => 'decimal:2',
        'final_quantity' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function freezingFish(): BelongsTo
    {
        return $this->belongsTo(FreezingFish::class, 'freezing_fish_id');
    }

    public function fishWarehouse(): BelongsTo
    {
        return $this->belongsTo(FishWarehouse::class, 'fish_warehouse_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class, 'container_id');
    }
}
