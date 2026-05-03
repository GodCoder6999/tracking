<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchItem extends Model
{
    protected $fillable = ['dispatch_id', 'order_item_id', 'qty'];

    public function dispatch(): BelongsTo  { return $this->belongsTo(Dispatch::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
