<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiveOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receive_order_id',
        'book_id',
        'ordered_quantity',
        'received_quantity',
        'purchase_price',
        'discount_percentage',
        'tax_percentage',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'integer',
            'received_quantity' => 'integer',
            'purchase_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
        ];
    }

    public function receiveOrder(): BelongsTo
    {
        return $this->belongsTo(ReceiveOrder::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
