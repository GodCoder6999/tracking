<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'particulars',
        'qty',
        'rate',
        'dealer_cost',
        'discount_percent',
        'gst_rate',
        'gst_amount',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'qty'              => 'integer',
            'rate'             => 'decimal:2',
            'dealer_cost'      => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'gst_rate'         => 'decimal:2',
            'gst_amount'       => 'decimal:2',
            'amount'           => 'decimal:2',
        ];
    }

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
