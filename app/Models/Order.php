<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const LABEL_PENDING    = 'pending';
    public const LABEL_PRINTED    = 'printed';
    public const LABEL_ATTACHED   = 'attached';

    public const PAYMENT_UNPAID   = 'unpaid';
    public const PAYMENT_PARTIAL  = 'partial';
    public const PAYMENT_PAID     = 'paid';

    public const DISPATCH_PENDING = 'pending';
    public const DISPATCH_PARTIAL = 'partial';
    public const DISPATCH_SENT    = 'sent';
    public const DISPATCH_DELIVERED = 'delivered';

    protected $fillable = [
        'order_number',
        'client_id',
        'dealer_id',
        'order_date',
        'label_status',
        'payment_status',
        'dispatch_status',
        'total_amount',
        'token_amount',
        'amount_received',
        'due_amount',
        'full_amount_date',
        'total_received',
        'total_received_date',
        'notes',
        'client_proof_path',
        'client_proofed_at',
        'shipping_address',
        'billing_address',
        'payment_method',
        'invoicing_terms',
        'shipping_method',
        'requested_delivery_date',
        'internal_notes',
        'attachment_path',
        'gst_amount',
        'discount_amount',
        'token_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'order_date'               => 'date',
            'full_amount_date'         => 'date',
            'total_received_date'      => 'date',
            'requested_delivery_date'  => 'date',
            'total_amount'             => 'decimal:2',
            'token_amount'             => 'decimal:2',
            'amount_received'          => 'decimal:2',
            'due_amount'               => 'decimal:2',
            'total_received'           => 'decimal:2',
            'gst_amount'               => 'decimal:2',
            'discount_amount'          => 'decimal:2',
            'client_proofed_at'        => 'datetime',
        ];
    }

    public function client(): BelongsTo   { return $this->belongsTo(User::class, 'client_id'); }
    public function dealer(): BelongsTo   { return $this->belongsTo(User::class, 'dealer_id'); }
    public function items(): HasMany      { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany   { return $this->hasMany(Payment::class); }
    public function dispatches(): HasMany { return $this->hasMany(Dispatch::class); }
}
