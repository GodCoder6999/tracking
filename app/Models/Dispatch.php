<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'dispatch_qty',
        'due_qty',
        'dispatch_date',
        'bill_path',
        'courier',
        'tracking_number',
        'tracking_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_qty'  => 'integer',
            'due_qty'       => 'integer',
            'dispatch_date' => 'date',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
