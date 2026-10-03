<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforceAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workforce_id',
        'date',
        'daily_rate',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'daily_rate' => 'decimal:2',
    ];

    public function workforce(): BelongsTo
    {
        return $this->belongsTo(Workforce::class, 'workforce_id');
    }
}
