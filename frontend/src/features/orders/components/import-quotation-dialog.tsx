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
import { useCustomerQuotations, useQuotationWithItems } from "@/features/orders/hooks/use-customer-quotations";
import type { QuotationItem } from "@/types/quotation";

interface SelectedQuotationItem {
  quotation_item_id: number;
  quotation_id: number;
  quotation_serial: string;
  description: string;
  unit: string;
  ordered_quantity: string;
  unit_price: string;
  discount_percentage: string;
  tax_percentage: string;
  quoted_quantity: string;
  remarks: string;
}

interface ImportQuotationDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  customerId: number | null;
  onImport: (items: SelectedQuotationItem[]) => void;
}

export function ImportQuotationDialog({
  open,
  onOpenChange,
  customerId,
  onImport,
}: ImportQuotationDialogProps) {
  const [selectedQuotationId, setSelectedQuotationId] = useState<number | null>(null);
  const [selectedItemIds, setSelectedItemIds] = useState<Set<number>>(new Set());

  const { data: quotations = [], isLoading: isLoadingQuotations } = useCustomerQuotations(customerId);
  const { data: quotationDetail, isLoading: isLoadingItems } = useQuotationWithItems(selectedQuotationId);

  useEffect(() => {
    if (open) {
      setSelectedQuotationId(null);
      setSelectedItemIds(new Set());
    }
  }, [open]);

  useEffect(() => {
    setSelectedItemIds(new Set());
  }, [selectedQuotationId]);

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
    if (!quotationDetail) return;

    const items: SelectedQuotationItem[] = quotationDetail.items
      .filter((item) => item.id && selectedItemIds.has(item.id))
      .map((item) => ({
        quotation_item_id: item.id!,
        quotation_id: quotationDetail.id,
        quotation_serial: quotationDetail.quotation_serial,
        description: item.description,
        unit: item.unit,
        ordered_quantity: item.quantity,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage,
        tax_percentage: item.tax_percentage,
        quoted_quantity: item.quantity,
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
          <DialogTitle>Import From Quotation</DialogTitle>
        </DialogHeader>

        {isLoadingQuotations ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="size-6 animate-spin text-muted-foreground" />
          </div>
        ) : quotations.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">
            No quotations found for this customer.
          </p>
        ) : (
          <div className="space-y-4">
            <div className="flex gap-4">
              <div className="w-1/3 space-y-1">
                <p className="text-xs font-medium text-muted-foreground">Select Quotation</p>
                <div className="max-h-64 space-y-1 overflow-y-auto">
                  {quotations.map((q) => (
                    <button
                      key={q.id}
                      type="button"
                      onClick={() => setSelectedQuotationId(q.id)}
                      className={`w-full rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted ${
                        selectedQuotationId === q.id ? "bg-primary/10 font-medium" : ""
                      }`}
                    >
                      <div>{q.quotation_serial}</div>
                      <div className="text-xs text-muted-foreground">
                        {fmtDate(q.quotation_date)}
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
                ) : !selectedQuotationId ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    Select a quotation to view items.
                  </p>
                ) : quotationDetail && quotationDetail.items.length > 0 ? (
                  <div className="max-h-64 space-y-1 overflow-y-auto">
                    {quotationDetail.items.map((item) => (
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
                            Qty: {item.quantity} | Unit: {item.unit} | Rate: {item.unit_price}
                          </div>
                        </div>
                      </label>
                    ))}
                  </div>
                ) : (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    No items in this quotation.
                  </p>
                )}
              </div>
            </div>

            <p className="text-xs text-muted-foreground">
              Tip: Select items from different quotations one at a time. Your selections are added after clicking "Import".
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
