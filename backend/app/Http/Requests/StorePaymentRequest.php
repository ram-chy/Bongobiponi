<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:Cash,Bank Transfer,UPI,Cheque',
            'reference_no' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.invoice_id' => 'required|exists:invoices,id',
            'items.*.paid_amount' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $customerId = $this->input('customer_id');
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $invoice = Invoice::find($item['invoice_id'] ?? null);

                if (! $invoice) {
                    continue;
                }

                if ((int) $invoice->customer_id !== (int) $customerId) {
                    $validator->errors()->add(
                        "items.{$index}.invoice_id",
                        "Invoice {$invoice->serial} does not belong to the selected customer."
                    );
                }

                if (! in_array($invoice->payment_status, ['Unpaid', 'Partially Paid'])) {
                    $validator->errors()->add(
                        "items.{$index}.invoice_id",
                        "Invoice {$invoice->serial} is already paid."
                    );
                }

                $dueAmount = (float) $invoice->grand_total - (float) $invoice->paid_amount;
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
            'items.required' => 'At least one invoice payment is required.',
            'items.min' => 'At least one invoice payment is required.',
            'items.*.invoice_id.required' => 'Invoice selection is required.',
            'items.*.invoice_id.exists' => 'Selected invoice does not exist.',
            'items.*.paid_amount.required' => 'Paid amount is required.',
            'items.*.paid_amount.min' => 'Paid amount must be greater than zero.',
        ];
    }
}
