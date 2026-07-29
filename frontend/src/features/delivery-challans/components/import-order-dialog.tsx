"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2, Package } from "lucide-react";
import { useCustomerOrders } from "@/features/delivery-challans/hooks/use-customer-orders";
import { cn } from "@/lib/utils";

interface ImportItem {
  order_booking_id: number;
  order_booking_item_id: number;
  description: string;
  unit: string;
  ordered_quantity: string;
  remaining_order_quantity: string;
  unit_price: string;
  delivery_quantity: string;
}

interface ImportOrderDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  customerId: number | null;
  onImport: (items: ImportItem[]) => void;
}

export function ImportOrderDialog({
  open,
  onOpenChange,
  customerId,
  onImport,
}: ImportOrderDialogProps) {
  const { data: orders = [], isLoading } = useCustomerOrders(customerId);
  const [expandedOrder, setExpandedOrder] = useState<number | null>(null);
  const [selectedItems, setSelectedItems] = useState<Record<number, string>>({});

  const handleToggleOrder = (orderId: number) => {
    setExpandedOrder(expandedOrder === orderId ? null : orderId);
  };

  const handleQuantityChange = (itemId: number, quantity: string) => {
    setSelectedItems((prev) => ({
      ...prev,
      [itemId]: quantity,
    }));
  };

  const handleImport = () => {
    const items: ImportItem[] = [];

    orders.forEach((order) => {
      order.items.forEach((item) => {
        const qty = selectedItems[item.id];
        if (qty && parseFloat(qty) > 0) {
          items.push({
            order_booking_id: order.id,
            order_booking_item_id: item.id,
            description: item.description,
            unit: item.unit,
            ordered_quantity: item.ordered_quantity,
            remaining_order_quantity: item.remaining_order_quantity,
            unit_price: item.unit_price,
            delivery_quantity: qty,
          });
        }
      });
    });

    if (items.length > 0) {
      onImport(items);
      setSelectedItems({});
      setExpandedOrder(null);
      onOpenChange(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Import from Order</DialogTitle>
          <DialogDescription>
            Select items from the customer&apos;s orders to add to this delivery challan.
          </DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="size-6 animate-spin text-muted-foreground" />
          </div>
        ) : orders.length === 0 ? (
          <div className="flex flex-col items-center gap-2 py-12 text-muted-foreground">
            <Package className="size-8" />
            <p className="text-sm">No orders found for this customer.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {orders.map((order) => (
              <div key={order.id} className="rounded-lg border">
                <button
                  type="button"
                  className="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/50"
                  onClick={() => handleToggleOrder(order.id)}
                >
                  <div>
                    <p className="font-medium">{order.order_serial}</p>
                    <p className="text-xs text-muted-foreground">
                      {order.order_date} &middot; {order.status}
                    </p>
                  </div>
                  <span className="text-xs text-muted-foreground">
                    {order.items.length} items
                  </span>
                </button>

                {expandedOrder === order.id && (
                  <div className="border-t px-4 py-3">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="min-w-36">Description</TableHead>
                          <TableHead className="w-14">Unit</TableHead>
                          <TableHead className="w-18 text-right">Ordered</TableHead>
                          <TableHead className="w-18 text-right">Remaining</TableHead>
                          <TableHead className="w-20">Delivery Qty</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {order.items
                          .filter((item) => parseFloat(item.remaining_order_quantity) > 0)
                          .map((item) => {
                            const qty = selectedItems[item.id] ?? "";
                            const remaining = parseFloat(item.remaining_order_quantity);
                            const deliveryQty = parseFloat(qty) || 0;
                            const exceedsRemaining = deliveryQty > remaining;

                            return (
                              <TableRow key={item.id}>
                                <TableCell className="text-sm">
                                  {item.description}
                                </TableCell>
                                <TableCell className="text-sm">{item.unit}</TableCell>
                                <TableCell className="text-right text-sm">
                                  {item.ordered_quantity}
                                </TableCell>
                                <TableCell className="text-right text-sm font-medium">
                                  {item.remaining_order_quantity}
                                </TableCell>
                                <TableCell>
                                  <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max={remaining}
                                    placeholder="0"
                                    value={qty}
                                    onChange={(e) =>
                                      handleQuantityChange(item.id, e.target.value)
                                    }
                                    className={cn(
                                      "h-8 w-24",
                                      exceedsRemaining && "border-amber-500"
                                    )}
                                  />
                                  {exceedsRemaining && (
                                    <p className="text-xs text-amber-600">
                                      Max: {item.remaining_order_quantity}
                                    </p>
                                  )}
                                </TableCell>
                              </TableRow>
                            );
                          })}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}

        <DialogFooter className="gap-2">
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
            disabled={
              Object.values(selectedItems).filter((v) => parseFloat(v) > 0).length === 0
            }
          >
            Import Selected Items
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
