<?php

namespace App\Services;

use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SourceDocumentOwnershipService
{
    public function quotationItem(int $id): QuotationItem
    {
        $item = QuotationItem::findOrFail($id);
        $quotation = $this->findDocument(Quotation::class, $item->quotation_id);
        $this->ensureAccessible($quotation);
        $item->setRelation('quotation', $quotation);

        return $item;
    }

    public function orderItem(int $id): OrderItem
    {
        $item = OrderItem::findOrFail($id);
        $order = $this->findDocument(Order::class, $item->order_id);
        $this->ensureAccessible($order);
        $item->setRelation('order', $order);

        return $item;
    }

    public function salesOrderItem(int $id): SalesOrderItem
    {
        $item = SalesOrderItem::findOrFail($id);
        $salesOrder = $this->findDocument(SalesOrder::class, $item->sales_order_id);
        $this->ensureAccessible($salesOrder);
        $item->setRelation('salesOrder', $salesOrder);

        return $item;
    }

    public function deliveryChallanItem(int $id): DeliveryChallanItem
    {
        $item = DeliveryChallanItem::findOrFail($id);
        $deliveryChallan = $this->findDocument(DeliveryChallan::class, $item->delivery_challan_id);
        $this->ensureAccessible($deliveryChallan);
        $item->setRelation('deliveryChallan', $deliveryChallan);

        return $item;
    }

    public function deliveryChallan(int $id): DeliveryChallan
    {
        $deliveryChallan = $this->findDocument(DeliveryChallan::class, $id);
        $this->ensureAccessible($deliveryChallan);

        return $deliveryChallan;
    }

    public function invoice(int $id): Invoice
    {
        $invoice = $this->findDocument(Invoice::class, $id);
        $this->ensureAccessible($invoice);

        return $invoice;
    }

    public function ensureMatchesCustomer(int $customerId, Model $document): void
    {
        if ((int) $document->customer_id !== $customerId) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected source document does not belong to the selected customer.'],
            ]);
        }
    }

    /**
     * @template TModel of Model
     * @param class-string<TModel> $modelClass
     * @return TModel
     */
    private function findDocument(string $modelClass, int $id): Model
    {
        return $modelClass::withoutGlobalScopes()->withTrashed()->findOrFail($id);
    }

    private function ensureAccessible(Model $document): void
    {
        $user = auth()->user();

        if (! $user || ($user->hasRole('regular_user') && $document->created_by !== $user->id)) {
            throw new AuthorizationException('You do not have permission to access this source document.');
        }
    }
}
