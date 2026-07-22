<?php

namespace App\Models;

use App\Models\Scopes\CreatedByScope;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'document_reference_uuid',
    'sales_order_serial',
    'customer_id',
    'sales_order_source',
    'sales_order_date',
    'expected_delivery_date',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'grand_total',
    'currency',
    'exchange_rate',
    'status',
    'reference_notes',
    'notes',
    'created_by',
    'approved_by',
    'approved_at',
    'confirmed_at',
    'pdf_generated_at',
])]
class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new CreatedByScope);
    }

    protected function casts(): array
    {
        return [
            'sales_order_date' => 'date',
            'expected_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'approved_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
