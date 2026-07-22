<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentSerialGeneratorService $serialGenerator,
        private readonly ActivityLogService $activityLogService,
        private readonly SourceDocumentOwnershipService $sourceDocumentOwnership,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Payment::query()->with(['customer', 'creator', 'items.invoice']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 10), 100));
    }

    public function store(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['payment_no'] = $this->serialGenerator->generate();

            $totalAmount = collect($items)->sum('paid_amount');
            $data['total_amount'] = round($totalAmount, 2);

            /** @var Payment $payment */
            $payment = Payment::create($data);

            $this->syncItems($payment, $items);

            $this->recalculatePaymentStatus($payment);

            $this->activityLogService->logCreate('payment', 'payment', $payment->id, $data);

            return $payment->load([
                'customer',
                'creator',
                'items.invoice',
            ]);
        });
    }

    public function update(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            if (! empty($data)) {
                $data['updated_by'] = auth()->id();
                $oldData = $payment->only(array_keys($data));
                $payment->update($data);
                $this->activityLogService->logUpdate('payment', 'payment', $payment->id, $oldData, $data);
            }

            if ($items !== null) {
                $oldInvoiceIds = $payment->items()->pluck('invoice_id')->unique()->toArray();

                $payment->items()->delete();

                foreach ($oldInvoiceIds as $oldInvoiceId) {
                    $this->recalculateInvoicePaymentStatus(
                        $this->sourceDocumentOwnership->invoice($oldInvoiceId),
                    );
                }

                $this->syncItems($payment, $items);

                $totalAmount = $payment->items()->sum('paid_amount');
                $payment->update(['total_amount' => round($totalAmount, 2)]);
            }

            $payment = $payment->fresh();
            $this->recalculatePaymentStatus($payment);

            return $payment->load([
                'customer',
                'creator',
                'items.invoice',
            ]);
        });
    }

    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->load('items.invoice');

            $invoices = $payment->items->pluck('invoice')->filter();

            $payment->delete();

            foreach ($invoices as $invoice) {
                $this->recalculateInvoicePaymentStatus($invoice);
            }
        });
    }

    public function findTrashed(int $id): Payment
    {
        return Payment::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    public function restore(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment->restore();

            $payment->load('items.invoice');

            foreach ($payment->items as $item) {
                if ($item->invoice) {
                    $this->recalculateInvoicePaymentStatus($item->invoice);
                }
            }

            $this->recalculatePaymentStatus($payment);

            $this->activityLogService->logRestore('payment', 'payment', $payment->id);

            return $payment->load([
                'customer',
                'creator',
                'items.invoice',
            ]);
        });
    }

    public function recalculateInvoicePaymentStatus(Invoice $invoice): void
    {
        $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
        $paidAmount = (float) $lockedInvoice->paymentItems()
            ->whereHas('payment', fn ($q) => $q->whereNull('deleted_at'))
            ->sum('paid_amount');
        $grandTotal = (float) $lockedInvoice->grand_total;

        $paymentStatus = match (true) {
            $paidAmount <= 0 => 'Unpaid',
            $paidAmount >= $grandTotal => 'Paid',
            default => 'Partially Paid',
        };

        $lockedInvoice->update([
            'paid_amount' => round($paidAmount, 2),
            'payment_status' => $paymentStatus,
        ]);
    }

    public function getDueInvoices(int $customerId): array
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($customerId);

        $user = auth()->user();
        if ($user->hasRole('regular_user') && $customer->created_by !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to access this customer.');
        }

        return Invoice::where('customer_id', $customerId)
            ->whereIn('payment_status', ['Unpaid', 'Partially Paid'])
            ->get()
            ->map(function (Invoice $invoice) {
                $dueAmount = (float) $invoice->grand_total - (float) $invoice->paid_amount;
                return [
                    'id' => $invoice->id,
                    'serial' => $invoice->serial,
                    'invoice_date' => $invoice->invoice_date,
                    'grand_total' => (float) $invoice->grand_total,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'due_amount' => round($dueAmount, 2),
                    'payment_status' => $invoice->payment_status,
                ];
            })
            ->values()
            ->toArray();
    }

    private function recalculatePaymentStatus(Payment $payment): void
    {
        $payment->load('items.invoice');

        $statuses = $payment->items
            ->pluck('invoice.payment_status')
            ->filter()
            ->unique();

        $paymentStatus = match (true) {
            $statuses->isEmpty() || $statuses->contains('Unpaid') => 'Unpaid',
            $statuses->contains('Partially Paid') => 'Partially Paid',
            $statuses->every(fn ($s) => $s === 'Paid') => 'Paid',
            default => 'Paid',
        };

        if ($payment->payment_status !== $paymentStatus) {
            $payment->update(['payment_status' => $paymentStatus]);
        }
    }

    private function syncItems(Payment $payment, array $items): void
    {
        foreach ($items as $item) {
            $invoice = $this->sourceDocumentOwnership->invoice($item['invoice_id']);
            $this->sourceDocumentOwnership->ensureMatchesCustomer($payment->customer_id, $invoice);

            $payment->items()->create([
                'invoice_id' => $item['invoice_id'],
                'paid_amount' => $item['paid_amount'],
                'remarks' => $item['remarks'] ?? null,
            ]);

            $this->recalculateInvoicePaymentStatus($invoice);
        }
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('payment_no', 'like', "%{$search}%")
              ->orWhere('reference_no', 'like', "%{$search}%")
              ->orWhereHas('customer', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
              });
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['payment_no', 'payment_date', 'created_at', 'total_amount', 'payment_method', 'payment_status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
