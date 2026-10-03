<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableReceiptDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'consumable_type_id',
        'quantity',
        'unit',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function consumableType(): BelongsTo
    {
        return $this->belongsTo(ConsumableType::class, 'consumable_type_id');
    }
}
