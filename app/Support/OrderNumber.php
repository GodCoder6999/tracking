<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderNumber
{
    public static function next(): string
    {
        $year = Carbon::now()->year;
        $count = Order::whereYear('created_at', $year)->count() + 1;
        return sprintf('ORD-%d-%05d', $year, $count);
    }
}
