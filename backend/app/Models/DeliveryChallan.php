<?php

namespace App\Models;

use App\Models\Scopes\CreatedByScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'serial',
    'delivery_date',
    'customer_id',
    'delivery_address',
    'transport_name',
    'vehicle_number',
    'driver_name',
    'driver_mobile',
    'lr_number',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'grand_total',
    'currency',
    'exchange_rate',
    'remarks',
    'status',
    'delivery_by',
    'receiver_name',
    'created_by',
    'updated_by',
])]
class DeliveryChallan extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new CreatedByScope);
    }

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryChallanItem::class);
    }
}
