<?php

namespace App\Models;

use Database\Factories\SalesOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sales_order_id',
    'order_id',
    'order_item_id',
    'source_type',
    'item_no',
    'description',
    'unit',
    'ordered_quantity',
    'sales_order_quantity',
    'remaining_sales_quantity',
    'unit_price',
    'price_snapshot',
    'discount_percentage',
    'discount_amount',
    'discount_snapshot',
    'tax_percentage',
    'tax_amount',
    'tax_snapshot',
    'line_total',
    'remarks',
    'sort_order',
])]
class SalesOrderItem extends Model
{
    /** @use HasFactory<SalesOrderItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:2',
            'sales_order_quantity' => 'decimal:2',
            'remaining_sales_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(SalesOrderItemConversion::class);
    }
}
