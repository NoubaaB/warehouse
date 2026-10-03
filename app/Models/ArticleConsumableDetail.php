<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleConsumableDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_article_detail_id',
        'consumable_type_id',
        'quantity',
        'unit',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function voucherArticleDetail(): BelongsTo
    {
        return $this->belongsTo(VoucherArticleDetail::class, 'voucher_article_detail_id');
    }

    public function consumableType(): BelongsTo
    {
        return $this->belongsTo(ConsumableType::class, 'consumable_type_id');
    }
}
