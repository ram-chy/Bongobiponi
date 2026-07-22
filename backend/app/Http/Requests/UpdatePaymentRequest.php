<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'payment_date' => 'sometimes|date',
            'payment_method' => 'sometimes|in:Cash,Bank Transfer,UPI,Cheque',
            'reference_no' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.invoice_id' => 'required_with:items|exists:invoices,id',
            'items.*.paid_amount' => 'required_with:items|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $routePayment = $this->route('payment');

            if ($routePayment instanceof Payment) {
                $this->merge(['_payment_id' => $routePayment->id]);
            }

            foreach ($items as $index => $item) {
                $invoice = Invoice::find($item['invoice_id'] ?? null);

                if (! $invoice) {
                    continue;
                }

                $existingPaid = 0;
                if ($routePayment) {
                    $paymentId = $routePayment instanceof Payment ? $routePayment->id : (int) $routePayment;
                    $existingPaid = (float) $invoice->paymentItems()
                        ->whereHas('payment', fn ($q) => $q->where('id', $paymentId))
                        ->sum('paid_amount');
                }

                $adjustedPaid = (float) $invoice->paid_amount - $existingPaid;
                $dueAmount = (float) $invoice->grand_total - max($adjustedPaid, 0);
                $paidAmount = (float) ($item['paid_amount'] ?? 0);

                if ($paidAmount > $dueAmount) {
                    $validator->errors()->add(
                        "items.{$index}.paid_amount",
                        "Paid amount ({$paidAmount}) exceeds due amount ({$dueAmount}) for invoice {$invoice->serial}."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one invoice payment is required.',
            'items.*.invoice_id.required_with' => 'Invoice selection is required.',
            'items.*.invoice_id.exists' => 'Selected invoice does not exist.',
            'items.*.paid_amount.required_with' => 'Paid amount is required.',
            'items.*.paid_amount.min' => 'Paid amount must be greater than zero.',
        ];
    }
}
