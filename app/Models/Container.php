<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'capacity', 'unit', 'active'];

    protected $casts = [
        'capacity' => 'decimal:2',
        'active' => 'boolean',
    ];
}
