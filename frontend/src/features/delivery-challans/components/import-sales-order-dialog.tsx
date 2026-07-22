"use client";

import { useState, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";
import { useCustomerSalesOrders, useSalesOrderWithItems } from "@/features/delivery-challans/hooks/use-customer-sales-orders";
import type { SalesOrderItem } from "@/types/sales-order";

interface SelectedSalesOrderItem {
  sales_order_item_id: number;
  sales_order_id: number;
  order_id: number;
  order_item_id: number;
  source_type: string;
  sales_order_serial: string;
  description: string;
  unit: string;
  ordered_quantity: string;
  remaining_sales_quantity: string;
  unit_price: string;
  discount_percentage: string;
  tax_percentage: string;
  remarks: string;
}

interface ImportSalesOrderDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  customerId: number | null;
  onImport: (items: SelectedSalesOrderItem[]) => void;
}

export function ImportSalesOrderDialog({
  open,
  onOpenChange,
  customerId,
  onImport,
}: ImportSalesOrderDialogProps) {
  const [selectedSalesOrderId, setSelectedSalesOrderId] = useState<number | null>(null);
  const [selectedItemIds, setSelectedItemIds] = useState<Set<number>>(new Set());

  const { data: salesOrders = [], isLoading: isLoadingSalesOrders } = useCustomerSalesOrders(customerId);
  const { data: salesOrderDetail, isLoading: isLoadingItems } = useSalesOrderWithItems(selectedSalesOrderId);

  useEffect(() => {
    if (open) {
      setSelectedSalesOrderId(null);
      setSelectedItemIds(new Set());
    }
  }, [open]);

  useEffect(() => {
    setSelectedItemIds(new Set());
  }, [selectedSalesOrderId]);

  const toggleItem = (itemId: number) => {
    setSelectedItemIds((prev) => {
      const next = new Set(prev);
      if (next.has(itemId)) {
        next.delete(itemId);
      } else {
        next.add(itemId);
      }
      return next;
    });
  };

  const handleImport = () => {
    if (!salesOrderDetail) return;

    const items: SelectedSalesOrderItem[] = salesOrderDetail.items
      .filter((item) => item.id && selectedItemIds.has(item.id))
      .map((item) => ({
        sales_order_item_id: item.id,
        sales_order_id: salesOrderDetail.id,
        order_id: item.order_id,
        order_item_id: item.order_item_id,
        source_type: item.source_type,
        sales_order_serial: salesOrderDetail.sales_order_serial,
        description: item.description,
        unit: item.unit,
        ordered_quantity: item.ordered_quantity,
        remaining_sales_quantity: item.remaining_sales_quantity,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage,
        tax_percentage: item.tax_percentage,
        remarks: item.remarks ?? "",
      }));

    onImport(items);
    onOpenChange(false);
  };

  const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  const fmtDate = (d: string | undefined | null) => {
    if (!d) return "-";
    const parts = d.split("T")[0].split("-");
    if (parts.length !== 3) return d.slice(0, 10);
    return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Import From Sales Order</DialogTitle>
        </DialogHeader>

        {isLoadingSalesOrders ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="size-6 animate-spin text-muted-foreground" />
          </div>
        ) : salesOrders.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">
            No sales orders found for this customer.
          </p>
        ) : (
          <div className="space-y-4">
            <div className="flex gap-4">
              <div className="w-1/3 space-y-1">
                <p className="text-xs font-medium text-muted-foreground">Select Sales Order</p>
                <div className="max-h-64 space-y-1 overflow-y-auto">
                  {salesOrders.map((so) => (
                    <button
                      key={so.id}
                      type="button"
                      onClick={() => setSelectedSalesOrderId(so.id)}
                      className={`w-full rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted ${
                        selectedSalesOrderId === so.id ? "bg-primary/10 font-medium" : ""
                      }`}
                    >
                      <div>{so.sales_order_serial}</div>
                      <div className="text-xs text-muted-foreground">
                        {fmtDate(so.sales_order_date)}
                      </div>
                    </button>
                  ))}
                </div>
              </div>

              <div className="flex-1 space-y-1">
                <p className="text-xs font-medium text-muted-foreground">Items</p>
                {isLoadingItems ? (
                  <div className="flex items-center justify-center py-12">
                    <Loader2 className="size-5 animate-spin text-muted-foreground" />
                  </div>
                ) : !selectedSalesOrderId ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    Select a sales order to view items.
                  </p>
                ) : salesOrderDetail && salesOrderDetail.items.length > 0 ? (
                  <div className="max-h-64 space-y-1 overflow-y-auto">
                    {salesOrderDetail.items
                      .filter((item) => parseFloat(item.remaining_sales_quantity) > 0)
                      .map((item) => (
                        <label
                          key={item.id}
                          className="flex cursor-pointer items-start gap-3 rounded-md px-3 py-2 text-sm hover:bg-muted"
                        >
                          <input
                            type="checkbox"
                            checked={!!(item.id && selectedItemIds.has(item.id))}
                            onChange={() => item.id && toggleItem(item.id)}
                            className="mt-0.5 size-4 accent-primary"
                          />
                          <div className="flex-1">
                            <div>{item.description}</div>
                            <div className="text-xs text-muted-foreground">
                              Ordered: {item.ordered_quantity} | Remaining: {item.remaining_sales_quantity} | Rate: {item.unit_price} | {item.unit}
                            </div>
                          </div>
                        </label>
                      ))}
                    {salesOrderDetail.items.filter((item) => parseFloat(item.remaining_sales_quantity) > 0)
                      .length === 0 && (
                      <p className="py-4 text-center text-xs text-muted-foreground">
                        All items have been fully delivered.
                      </p>
                    )}
                  </div>
                ) : (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    No items in this sales order.
                  </p>
                )}
              </div>
            </div>

            <p className="text-xs text-muted-foreground">
              Tip: Select items from different sales orders one at a time. Selections are added after clicking "Import".
            </p>
          </div>
        )}

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
          >
            Cancel
          </Button>
          <Button
            type="button"
            onClick={handleImport}
            disabled={selectedItemIds.size === 0}
          >
            Add Selected ({selectedItemIds.size})
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
