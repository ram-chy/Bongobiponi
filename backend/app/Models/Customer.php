<?php

namespace App\Models;

use App\Models\Scopes\CreatedByScope;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'created_by',
    'customer_code',
    'name',
    'company_name',
    'email',
    'phone',
    'alternate_phone',
    'gst_number',
    'pan_number',
    'billing_address',
    'shipping_address',
    'city',
    'state',
    'country',
    'postal_code',
    'credit_limit',
    'opening_balance',
    'status',
    'notes',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new CreatedByScope);
    }

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public static function generateCustomerCode(): string
    {
        return DB::transaction(function () {
            $last = static::withTrashed()
                ->where('customer_code', 'like', 'BBCU/%')
                ->orderByRaw('CAST(SUBSTRING(customer_code, 6) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $lastNumber = $last ? (int) substr($last->customer_code, 5) : 0;

            return 'BBCU/' . Str::padLeft((string) ($lastNumber + 1), 3, '0');
        });
    }
}
