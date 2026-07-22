<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'current_quantity',
        'last_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'integer',
            'last_transaction_id' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function lastTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'last_transaction_id');
    }
}
