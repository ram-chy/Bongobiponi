<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delivery_challan_id',
    'sales_order_id',
    'sales_order_item_id',
    'order_booking_id',
    'order_booking_item_id',
    'quotation_id',
    'quotation_item_id',
    'item_description',
    'unit',
    'ordered_quantity',
    'delivered_quantity',
    'unit_price',
    'remarks',
])]
class DeliveryChallanItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:2',
            'delivered_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function deliveryChallan(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallan::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function orderBooking(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_booking_id');
    }

    public function orderBookingItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_booking_item_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }
}
