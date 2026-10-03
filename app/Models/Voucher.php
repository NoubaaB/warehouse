<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_number',
        'voucher_type_id',
        'fish_warehouse_id',
        'voucher_date',
        'truck_licence',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'voucher_date' => 'date:Y-m-d',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }

    public function fishWarehouse(): BelongsTo
    {
        return $this->belongsTo(FishWarehouse::class, 'fish_warehouse_id');
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'voucher_provider');
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'voucher_client');
    }

    public function articleDetails(): HasMany
    {
        return $this->hasMany(VoucherArticleDetail::class, 'voucher_id');
    }

    public function consumableReceiptDetails(): HasMany
    {
        return $this->hasMany(ConsumableReceiptDetail::class, 'voucher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
