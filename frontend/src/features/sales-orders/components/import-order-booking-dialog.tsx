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
import { useCustomerOrderBookings, useOrderWithItems } from "@/features/sales-orders/hooks/use-customer-order-bookings";
import type { OrderItem } from "@/types/order";

interface SelectedOrderItem {
  order_item_id: number;
  order_id: number;
  order_serial: string;
  source_type: string;
  description: string;
  unit: string;
  ordered_quantity: string;
  remaining_order_quantity: string;
  unit_price: string;
  discount_percentage: string;
  tax_percentage: string;
  quotation_serial?: string;
  original_source_type?: string;
  remarks: string;
}

interface ImportOrderBookingDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  customerId: number | null;
  onImport: (items: SelectedOrderItem[]) => void;
}

export function ImportOrderBookingDialog({
  open,
  onOpenChange,
  customerId,
  onImport,
}: ImportOrderBookingDialogProps) {
  const [selectedOrderId, setSelectedOrderId] = useState<number | null>(null);
  const [selectedItemIds, setSelectedItemIds] = useState<Set<number>>(new Set());

  const { data: orders = [], isLoading: isLoadingOrders } = useCustomerOrderBookings(customerId);
  const { data: orderDetail, isLoading: isLoadingItems } = useOrderWithItems(selectedOrderId);

  useEffect(() => {
    if (open) {
      setSelectedOrderId(null);
      setSelectedItemIds(new Set());
    }
  }, [open]);

  useEffect(() => {
    setSelectedItemIds(new Set());
  }, [selectedOrderId]);

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
    if (!orderDetail) return;

    const items: SelectedOrderItem[] = orderDetail.items
      .filter((item) => item.id && selectedItemIds.has(item.id))
      .map((item) => ({
        order_item_id: item.id,
        order_id: orderDetail.id,
        order_serial: orderDetail.order_serial,
        source_type: item.source_type,
        description: item.description,
        unit: item.unit,
        ordered_quantity: item.ordered_quantity,
        remaining_order_quantity: item.remaining_order_quantity,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage,
        tax_percentage: item.tax_percentage,
        original_source_type: item.source_type,
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
          <DialogTitle>Import From Order Booking</DialogTitle>
        </DialogHeader>

        {isLoadingOrders ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="size-6 animate-spin text-muted-foreground" />
          </div>
        ) : orders.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">
            No order bookings found for this customer.
          </p>
        ) : (
          <div className="space-y-4">
            <div className="flex gap-4">
              <div className="w-1/3 space-y-1">
                <p className="text-xs font-medium text-muted-foreground">Select Order Booking</p>
                <div className="max-h-64 space-y-1 overflow-y-auto">
                  {orders.map((o) => (
                    <button
                      key={o.id}
                      type="button"
                      onClick={() => setSelectedOrderId(o.id)}
                      className={`w-full rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted ${
                        selectedOrderId === o.id ? "bg-primary/10 font-medium" : ""
                      }`}
                    >
                      <div>{o.order_serial}</div>
                      <div className="text-xs text-muted-foreground">
                        {fmtDate(o.order_date)}
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
                ) : !selectedOrderId ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    Select an order to view items.
                  </p>
                ) : orderDetail && orderDetail.items.length > 0 ? (
                  <div className="max-h-64 space-y-1 overflow-y-auto">
                    {orderDetail.items.map((item) => (
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
                            Ordered: {item.ordered_quantity} | Remaining: {item.remaining_order_quantity} | Rate: {item.unit_price} | {item.unit}
                          </div>
                        </div>
                      </label>
                    ))}
                  </div>
                ) : (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    No items in this order.
                  </p>
                )}
              </div>
            </div>

            <p className="text-xs text-muted-foreground">
              Tip: Select items from different orders one at a time. Your selections are added after clicking "Import".
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
