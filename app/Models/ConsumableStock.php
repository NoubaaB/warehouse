<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumable_type_id',
        'total_received',
        'total_consumed',
        'current_quantity',
        'unit',
    ];

    protected $casts = [
        'total_received' => 'decimal:2',
        'total_consumed' => 'decimal:2',
        'current_quantity' => 'decimal:2',
    ];

    public function consumableType(): BelongsTo
    {
        return $this->belongsTo(ConsumableType::class, 'consumable_type_id');
    }
}
