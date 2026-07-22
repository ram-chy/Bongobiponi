"use client";

import { useFieldArray, useWatch, type UseFormReturn } from "react-hook-form";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Trash2, Plus } from "lucide-react";

const UNIT_OPTIONS = ["Pcs", "Nos", "Mtr", "Kg", "Ltr", "Box", "Pkt", "Set"];

interface QuotationItemsTableProps {
  form: UseFormReturn;
  disabled?: boolean;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type ItemValue = Record<string, any>;

function calcLineTotal(item: ItemValue): string {
  const qty = parseFloat(item.quantity) || 0;
  const price = parseFloat(item.unit_price) || 0;
  const discPct = parseFloat(item.discount_percentage || "0") || 0;
  const taxPct = parseFloat(item.tax_percentage || "0") || 0;
  const base = qty * price;
  const discAmt = base * (discPct / 100);
  const taxAmt = (base - discAmt) * (taxPct / 100);
  return (base - discAmt + taxAmt).toFixed(2);
}

function calcTotals(items: ItemValue[]) {
  let subtotal = 0;
  let totalDisc = 0;
  let totalTax = 0;
  let grandTotal = 0;
  for (const item of items) {
    const qty = parseFloat(item.quantity) || 0;
    const price = parseFloat(item.unit_price) || 0;
    const discPct = parseFloat(item.discount_percentage || "0") || 0;
    const taxPct = parseFloat(item.tax_percentage || "0") || 0;
    const base = qty * price;
    const discAmt = base * (discPct / 100);
    const taxAmt = (base - discAmt) * (taxPct / 100);
    subtotal += base;
    totalDisc += discAmt;
    totalTax += taxAmt;
    grandTotal += base - discAmt + taxAmt;
  }
  return {
    subtotal: subtotal.toFixed(2),
    totalDiscount: totalDisc.toFixed(2),
    totalTax: totalTax.toFixed(2),
    grandTotal: grandTotal.toFixed(2),
  };
}

export function QuotationItemsTable({
  form,
  disabled,
}: QuotationItemsTableProps) {
  const { control, register, setValue } = form;

  const { fields, append, remove } = useFieldArray({
    control,
    name: "items",
  });

  const items = useWatch({ control, name: "items" }) ?? [];
  const totals = calcTotals(items);

  return (
    <div className="space-y-4">
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50">
              <th className="p-2 text-left font-medium">#</th>
              <th className="p-2 text-left font-medium">Description *</th>
              <th className="p-2 text-left font-medium">Unit</th>
              <th className="p-2 text-right font-medium">Qty *</th>
              <th className="p-2 text-right font-medium">Rate *</th>
              <th className="p-2 text-right font-medium">Disc %</th>
              <th className="p-2 text-right font-medium">Tax %</th>
              <th className="p-2 text-right font-medium">Amount</th>
              <th className="p-2 text-left font-medium">Remarks</th>
              <th className="w-10 p-2" />
            </tr>
          </thead>
          <tbody>
            {fields.map((field, index) => {
              const item = items[index];
              return (
                <tr key={field.id} className="border-b last:border-0">
                  <td className="p-2 text-muted-foreground">{index + 1}</td>
                  <td className="p-2">
                    <Input
                      {...register(`items.${index}.description`)}
                      placeholder="Description"
                      disabled={disabled}
                      className="h-8"
                    />
                  </td>
                  <td className="p-2">
                    <Select
                      value={item?.unit ?? null}
                      onValueChange={(v) => setValue(`items.${index}.unit`, v ?? "")}
                      disabled={disabled}
                      items={UNIT_OPTIONS.map((u) => ({ value: u, label: u }))}
                    >
                      <SelectTrigger className="h-8">
                        <SelectValue placeholder="Unit" />
                      </SelectTrigger>
                      <SelectContent>
                        {UNIT_OPTIONS.map((u) => (
                          <SelectItem key={u} value={u}>
                            {u}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </td>
                  <td className="p-2">
                    <Input
                      {...register(`items.${index}.quantity`)}
                      type="number"
                      step="any"
                      min="0"
                      placeholder="0"
                      disabled={disabled}
                      className="h-8 text-right"
                    />
                  </td>
                  <td className="p-2">
                    <Input
                      {...register(`items.${index}.unit_price`)}
                      type="number"
                      step="any"
                      min="0"
                      placeholder="0"
                      disabled={disabled}
                      className="h-8 text-right"
                    />
                  </td>
                  <td className="p-2">
                    <Input
                      {...register(`items.${index}.discount_percentage`)}
                      type="number"
                      step="any"
                      min="0"
                      max="100"
                      placeholder="0"
                      disabled={disabled}
                      className="h-8 text-right"
                    />
                  </td>
                  <td className="p-2">
                    <Input
                      {...register(`items.${index}.tax_percentage`)}
                      type="number"
                      step="any"
                      min="0"
                      max="100"
                      placeholder="0"
                      disabled={disabled}
                      className="h-8 text-right"
                    />
                  </td>
                  <td className="p-2 text-right font-medium">
                    {item ? calcLineTotal(item) : "0.00"}
                  </td>
                  <td className="p-2">
                    <Input
                      {...register(`items.${index}.remarks`)}
                      placeholder="Remarks"
                      disabled={disabled}
                      className="h-8"
                    />
                  </td>
                  <td className="p-2">
                    {!disabled && fields.length > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => remove(index)}
                        className="size-8 text-destructive"
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {!disabled && (
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() =>
            append({
              description: "",
              quantity: "",
              unit: "",
              unit_price: "",
              discount_percentage: "",
              tax_percentage: "",
              remarks: "",
            })
          }
          className="gap-1"
        >
          <Plus className="size-4" />
          Add Item
        </Button>
      )}

      <div className="ml-auto w-full max-w-xs space-y-1 rounded-lg border p-4">
        <div className="flex justify-between text-sm">
          <span className="text-muted-foreground">Subtotal</span>
          <span>{totals.subtotal}</span>
        </div>
        <div className="flex justify-between text-sm">
          <span className="text-muted-foreground">Discount</span>
          <span>{totals.totalDiscount}</span>
        </div>
        <div className="flex justify-between text-sm">
          <span className="text-muted-foreground">Tax</span>
          <span>{totals.totalTax}</span>
        </div>
        <div className="flex justify-between border-t pt-1 font-medium">
          <span>Grand Total</span>
          <span>{totals.grandTotal}</span>
        </div>
      </div>
    </div>
  );
}
