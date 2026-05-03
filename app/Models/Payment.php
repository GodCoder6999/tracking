<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'payment_date',
        'received_date',
        'screenshot_path',
        'method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'payment_date'  => 'date',
            'received_date' => 'date',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
