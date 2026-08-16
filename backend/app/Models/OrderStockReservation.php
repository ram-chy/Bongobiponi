<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'book_id',
    'quantity',
    'status',
    'consumed_by_inventory_transaction_id',
])]
class OrderStockReservation extends Model
{
    /** @use HasFactory<OrderStockReservationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function consumedByTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'consumed_by_inventory_transaction_id');
    }
}