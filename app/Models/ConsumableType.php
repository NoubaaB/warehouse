<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsumableType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function stock(): HasOne
    {
        return $this->hasOne(ConsumableStock::class, 'consumable_type_id');
    }
}
