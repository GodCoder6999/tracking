<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'rate', 'dealer_cost', 'stock', 'is_active', 'catalog_path'];

    protected function casts(): array
    {
        return [
            'rate'        => 'decimal:2',
            'dealer_cost' => 'decimal:2',
            'is_active'   => 'boolean',
        ];
    }
}
