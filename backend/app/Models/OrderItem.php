<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'quotation_id',
    'quotation_item_id',
    'source_type',
    'item_no',
    'description',
    'unit',
    'quoted_quantity',
    'ordered_quantity',
    'remaining_order_quantity',
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
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quoted_quantity' => 'decimal:2',
            'ordered_quantity' => 'decimal:2',
            'remaining_order_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(OrderItemConversion::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
