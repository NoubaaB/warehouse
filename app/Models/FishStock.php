<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'freezing_fish_id',
        'fish_warehouse_id',
        'container_id',
        'reception_date',
        'provider_names',
        'original_quantity',
        'remaining_quantity',
        'calculated_boxes',
        'status',
    ];

    protected $casts = [
        'reception_date' => 'date:Y-m-d',
        'original_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'calculated_boxes' => 'decimal:2',
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
