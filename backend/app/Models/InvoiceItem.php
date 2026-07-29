<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'delivery_challan_id',
    'delivery_challan_item_id',
    'order_booking_id',
    'order_booking_item_id',
    'quotation_id',
    'quotation_item_id',
    'item_description',
    'unit',
    'delivered_quantity',
    'invoiced_quantity',
    'remaining_invoice_quantity',
    'unit_price',
    'line_total',
    'remarks',
])]
class InvoiceItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'delivered_quantity' => 'decimal:2',
            'invoiced_quantity' => 'decimal:2',
            'remaining_invoice_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function deliveryChallan(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallan::class);
    }

    public function deliveryChallanItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallanItem::class);
    }

    public function orderBooking(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_booking_id');
    }

    public function orderBookingItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_booking_item_id');
    }

}
