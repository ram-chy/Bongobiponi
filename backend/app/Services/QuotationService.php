<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(
        private readonly QuotationSerialGeneratorService $serialGenerator,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Quotation::query()->with(['customer', 'creator']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['quotation_serial'] = $this->serialGenerator->generate();

            $calculated = $this->calculateItemTotals($items);
            $data['subtotal'] = $calculated['subtotal'];
            $data['discount_amount'] = $calculated['discount_amount'];
            $data['tax_amount'] = $calculated['tax_amount'];
            $data['grand_total'] = $calculated['grand_total'];

            /** @var Quotation $quotation */
            $quotation = Quotation::create($data);

            $this->syncItems($quotation, $items);

            return $quotation->load(['customer', 'creator', 'items']);
        });
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            if ($items !== null) {
                $calculated = $this->calculateItemTotals($items);
                $data['subtotal'] = $calculated['subtotal'];
                $data['discount_amount'] = $calculated['discount_amount'];
                $data['tax_amount'] = $calculated['tax_amount'];
                $data['grand_total'] = $calculated['grand_total'];
            }

            $quotation->update($data);

            if ($items !== null) {
                $quotation->items()->delete();
                $this->syncItems($quotation, $items);
            }

            return $quotation->load(['customer', 'creator', 'items']);
        });
    }

    public function findTrashed(int $id): Quotation
    {
        return Quotation::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function restore(Quotation $quotation): Quotation
    {
        $quotation->restore();

        return $quotation->load(['customer', 'creator', 'items']);
    }

    private function syncItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $index => $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercentage = (float) ($item['discount_percentage'] ?? 0);
            $taxPercentage = (float) ($item['tax_percentage'] ?? 0);

            $baseAmount = $quantity * $unitPrice;
            $discountAmount = $baseAmount * ($discountPercentage / 100);
            $taxableAmount = $baseAmount - $discountAmount;
            $taxAmount = $taxableAmount * ($taxPercentage / 100);
            $lineTotal = $baseAmount - $discountAmount + $taxAmount;

            $quotation->items()->create([
                'item_no' => $index + 1,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit' => $item['unit'],
                'unit_price' => $unitPrice,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
                'sort_order' => $index + 1,
                'remaining_quantity' => $quantity,
            ]);
        }
    }

    private function calculateItemTotals(array $items): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTax = 0;
        $grandTotal = 0;

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discountPercentage = (float) ($item['discount_percentage'] ?? 0);
            $taxPercentage = (float) ($item['tax_percentage'] ?? 0);

            $baseAmount = $quantity * $unitPrice;
            $discountAmount = $baseAmount * ($discountPercentage / 100);
            $taxableAmount = $baseAmount - $discountAmount;
            $taxAmount = $taxableAmount * ($taxPercentage / 100);
            $lineTotal = $baseAmount - $discountAmount + $taxAmount;

            $subtotal += $baseAmount;
            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
            $grandTotal += $lineTotal;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($totalDiscount, 2),
            'tax_amount' => round($totalTax, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('quotation_serial', 'like', "%{$search}%")
              ->orWhere('status', 'like', "%{$search}%")
              ->orWhereHas('customer', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
              });
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('quotation_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('quotation_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['quotation_serial', 'quotation_date', 'valid_until', 'grand_total', 'status', 'created_at'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
