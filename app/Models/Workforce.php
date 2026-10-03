<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workforce extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'identifier', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkforceAssignment::class, 'workforce_id');
    }
}
