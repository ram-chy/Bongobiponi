"use client";

import { useState, useEffect, useMemo } from "react";
import { useForm, useFieldArray, useWatch, type UseFormReturn } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Loader2, ArrowLeft, Search, Plus, Trash2, FileText, ArrowUp, ArrowDown } from "lucide-react";
import { useCustomerSearch } from "@/hooks/use-customer-search";
import { useSalesOrderForm as useSalesOrderFormMutation } from "@/features/sales-orders/hooks/use-sales-order-form";
import { ImportOrderBookingDialog } from "@/features/sales-orders/components/import-order-booking-dialog";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Customer } from "@/types/customer";

const itemSchema = z.object({
  order_item_id: z.string().min(1, "Required"),
  order_id: z.string().min(1, "Required"),
  source_type: z.string(),
  description: z.string().min(1, "Description is required"),
  unit: z.string().min(1, "Unit is required"),
  ordered_quantity: z.string(),
  sales_order_quantity: z.string().min(1, "SO quantity is required"),
  remaining_order_quantity: z.string(),
  unit_price: z.string().min(1, "Unit price is required"),
  discount_percentage: z.string().optional().or(z.literal("")),
  tax_percentage: z.string().optional().or(z.literal("")),
  remarks: z.string().optional().or(z.literal("")),
  order_serial: z.string().optional(),
  quotation_serial: z.string().optional(),
  original_source_type: z.string().optional(),
});

const salesOrderSchema = z.object({
  customer_id: z.string().min(1, "Customer is required"),
  sales_order_date: z.string().min(1, "Sales order date is required"),
  expected_delivery_date: z.string().optional().or(z.literal("")),
  reference_notes: z.string().optional().or(z.literal("")),
  notes: z.string().optional().or(z.literal("")),
  items: z.array(itemSchema).min(1, "At least one item is required"),
});

export type SalesOrderFormValues = z.infer<typeof salesOrderSchema>;

interface SalesOrderFormProps {
  defaultValues?: Partial<SalesOrderFormValues>;
  id?: number;
  customer?: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null;
}

export function SalesOrderForm({
  defaultValues,
  id,
  customer: initialCustomer,
}: SalesOrderFormProps) {
  const router = useRouter();
  const salesOrderMutation = useSalesOrderFormMutation({ id });
  const [customerSearch, setCustomerSearch] = useState("");
  const [customerDropdownOpen, setCustomerDropdownOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null>(null);
  const [importDialogOpen, setImportDialogOpen] = useState(false);

  const { data: customers = [] } = useCustomerSearch(customerSearch);

  const form = useForm<SalesOrderFormValues>({
    resolver: zodResolver(salesOrderSchema),
    defaultValues: {
      sales_order_date: new Date().toISOString().split("T")[0],
      expected_delivery_date: "",
      reference_notes: "",
      notes: "",
      items: [],
      ...defaultValues,
      ...(defaultValues?.customer_id ? { customer_id: defaultValues.customer_id } : {}),
    },
  });

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    watch,
    control,
    reset,
    formState: { errors, isSubmitting },
  } = form;

  const { fields, append, remove, swap } = useFieldArray({ control, name: "items" });
  const items = useWatch({ control, name: "items" });

  useEffect(() => {
    if (defaultValues) {
      reset({
        ...defaultValues,
        items: defaultValues.items ?? [],
      });
    }
  }, [defaultValues, reset]);

  useEffect(() => {
    if (defaultValues?.sales_order_date) {
      setCustomerSearch("");
    }
  }, [defaultValues]);

  useEffect(() => {
    if (initialCustomer) {
      setSelectedCustomer(initialCustomer);
    }
  }, [initialCustomer]);

  const totals = useMemo(() => {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;

    (items ?? []).forEach((item) => {
      const qty = parseFloat(item.sales_order_quantity) || 0;
      const price = parseFloat(item.unit_price) || 0;
      const discPct = parseFloat(item.discount_percentage ?? "0") || 0;
      const taxPct = parseFloat(item.tax_percentage ?? "0") || 0;

      const base = qty * price;
      const disc = base * (discPct / 100);
      const tax = (base - disc) * (taxPct / 100);

      subtotal += base;
      totalDiscount += disc;
      totalTax += tax;
    });

    return {
      subtotal: subtotal.toFixed(2),
      discountAmount: totalDiscount.toFixed(2),
      taxAmount: totalTax.toFixed(2),
      grandTotal: (subtotal - totalDiscount + totalTax).toFixed(2),
    };
  }, [items]);

  const onSubmit = async (data: SalesOrderFormValues) => {
    try {
      const payload = {
        ...data,
        items: data.items.map((item) => ({
          order_item_id: item.order_item_id,
          sales_order_quantity: item.sales_order_quantity,
          remarks: item.remarks || undefined,
        })),
      };
      await salesOrderMutation.mutateAsync(
        payload as unknown as Record<string, unknown>
      );
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  const handleSelectCustomer = (customer: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number">) => {
    setSelectedCustomer(customer);
    setValue("customer_id", String(customer.id));
    setCustomerDropdownOpen(false);
    setCustomerSearch("");
  };

  const handleImportItems = (importedItems: Array<{
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
  }>) => {
    importedItems.forEach((item) => {
      append({
        order_item_id: String(item.order_item_id),
        order_id: String(item.order_id),
        order_serial: item.order_serial,
        source_type: item.source_type,
        description: item.description,
        unit: item.unit,
        ordered_quantity: item.ordered_quantity,
        sales_order_quantity: item.remaining_order_quantity,
        remaining_order_quantity: item.remaining_order_quantity,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage,
        tax_percentage: item.tax_percentage,
        quotation_serial: item.quotation_serial,
        original_source_type: item.original_source_type,
        remarks: item.remarks,
      });
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/sales-orders")}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Sales Orders
        </Button>
      </div>

      <div className="grid gap-6">
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Sales Order Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {selectedCustomer ? (
              <div>
                <Label>Customer</Label>
                <p className="text-sm font-medium text-muted-foreground">
                  {selectedCustomer.company_name || selectedCustomer.name}
                </p>
              </div>
            ) : (
              <div className="space-y-2">
                <Label>Customer *</Label>
                <div className="relative">
                  <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    placeholder="Search customer..."
                    className="pl-8"
                    value={customerSearch}
                    onChange={(e) => {
                      setCustomerSearch(e.target.value);
                      setCustomerDropdownOpen(true);
                    }}
                    onFocus={() => setCustomerDropdownOpen(true)}
                  />
                  {customerDropdownOpen && customers.length > 0 && (
                    <div className="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-popover p-1 shadow-md">
                      {customers.map((customer) => (
                        <button
                          key={customer.id}
                          type="button"
                          className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                          onClick={() => handleSelectCustomer(customer)}
                        >
                          <div>
                            <p className="font-medium">
                              {customer.company_name || customer.name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                              {customer.name} | {customer.phone}
                            </p>
                          </div>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
                {errors.customer_id && (
                  <p className="text-sm text-destructive">
                    {errors.customer_id.message}
                  </p>
                )}
              </div>
            )}

            {selectedCustomer && (
              <div className="grid gap-2 rounded-lg border p-3 text-sm sm:grid-cols-2">
                <div>
                  <span className="text-muted-foreground">Company: </span>
                  <span className="font-medium">
                    {selectedCustomer.company_name || "-"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Contact: </span>
                  <span className="font-medium">{selectedCustomer.name}</span>
                </div>
                <div>
                  <span className="text-muted-foreground">Address: </span>
                  <span className="font-medium">
                    {selectedCustomer.billing_address}, {selectedCustomer.city}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground">Mobile: </span>
                  <span className="font-medium">{selectedCustomer.phone}</span>
                </div>
                {selectedCustomer.email && (
                  <div>
                    <span className="text-muted-foreground">Email: </span>
                    <span className="font-medium">{selectedCustomer.email}</span>
                  </div>
                )}
                {selectedCustomer.gst_number && (
                  <div>
                    <span className="text-muted-foreground">GST: </span>
                    <span className="font-medium">{selectedCustomer.gst_number}</span>
                  </div>
                )}
              </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="sales_order_date">Sales Order Date *</Label>
                <input
                  id="sales_order_date"
                  type="date"
                  {...register("sales_order_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.sales_order_date && (
                  <p className="text-sm text-destructive">
                    {errors.sales_order_date.message}
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="expected_delivery_date">Expected Delivery Date</Label>
                <input
                  id="expected_delivery_date"
                  type="date"
                  {...register("expected_delivery_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="reference_notes">Reference Number</Label>
              <Input
                id="reference_notes"
                placeholder="PO number, reference, etc."
                {...register("reference_notes")}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="notes">Notes</Label>
              <Textarea
                rows={3}
                {...register("notes")}
                placeholder="Additional notes..."
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>Items</CardTitle>
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="gap-1.5"
                disabled={!selectedCustomer}
                onClick={() => setImportDialogOpen(true)}
              >
                <FileText className="size-4" />
                Import From Order Booking
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {fields.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No items yet. Import from an Order Booking to get started.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-20">Source</TableHead>
                      <TableHead className="w-32">Order</TableHead>
                      <TableHead className="min-w-40">Description</TableHead>
                      <TableHead className="w-16">Unit</TableHead>
                      <TableHead className="w-20">Order Qty</TableHead>
                      <TableHead className="w-20">Remaining</TableHead>
                      <TableHead className="w-20">SO Qty</TableHead>
                      <TableHead className="w-20">Rate</TableHead>
                      <TableHead className="w-24">Line Total</TableHead>
                      <TableHead className="min-w-28">Remarks</TableHead>
                      <TableHead className="w-16" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {fields.map((field, index) => {
                      const qty = parseFloat(items?.[index]?.sales_order_quantity ?? "0") || 0;
                      const price = parseFloat(items?.[index]?.unit_price ?? "0") || 0;
                      const lineTotal = (qty * price).toFixed(2);
                      const remaining = parseFloat(items?.[index]?.remaining_order_quantity ?? "0") || 0;
                      const soQty = parseFloat(items?.[index]?.sales_order_quantity ?? "0") || 0;
                      const exceedsRemaining = soQty > remaining;

                      return (
                        <TableRow key={field.id}>
                          <TableCell>
                            <input type="hidden" {...register(`items.${index}.order_item_id`)} />
                            <input type="hidden" {...register(`items.${index}.order_id`)} />
                            <input type="hidden" {...register(`items.${index}.source_type`)} />
                            <input type="hidden" {...register(`items.${index}.ordered_quantity`)} />
                            <input type="hidden" {...register(`items.${index}.remaining_order_quantity`)} />
                            <input type="hidden" {...register(`items.${index}.order_serial`)} />
                            <input type="hidden" {...register(`items.${index}.quotation_serial`)} />
                            <input type="hidden" {...register(`items.${index}.original_source_type`)} />
                            <input type="hidden" {...register(`items.${index}.discount_percentage`)} />
                            <input type="hidden" {...register(`items.${index}.tax_percentage`)} />
                            <span className="text-xs font-medium uppercase text-muted-foreground">
                              {field.original_source_type ?? field.source_type}
                            </span>
                          </TableCell>
                          <TableCell className="text-xs text-muted-foreground">
                            {items?.[index]?.order_serial ?? "-"}
                          </TableCell>
                          <TableCell>
                            <Input
                              placeholder="Description"
                              {...register(`items.${index}.description`)}
                            />
                            {errors.items?.[index]?.description && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.description?.message}
                              </p>
                            )}
                          </TableCell>
                          <TableCell>
                            <Input
                              placeholder="Unit"
                              {...register(`items.${index}.unit`)}
                            />
                            {errors.items?.[index]?.unit && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.unit?.message}
                              </p>
                            )}
                          </TableCell>
                          <TableCell className="text-right text-sm">
                            {items?.[index]?.ordered_quantity ?? "-"}
                          </TableCell>
                          <TableCell className="text-right text-sm font-medium">
                            {items?.[index]?.remaining_order_quantity ?? "-"}
                          </TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              step="0.01"
                              min="0.01"
                              placeholder="0"
                              className={exceedsRemaining ? "border-amber-500" : ""}
                              {...register(`items.${index}.sales_order_quantity`)}
                            />
                            {errors.items?.[index]?.sales_order_quantity && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.sales_order_quantity?.message}
                              </p>
                            )}
                            {exceedsRemaining && (
                              <p className="text-xs text-amber-600">
                                Max: {items?.[index]?.remaining_order_quantity}
                              </p>
                            )}
                          </TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              step="0.01"
                              min="0"
                              placeholder="0.00"
                              {...register(`items.${index}.unit_price`)}
                            />
                            {errors.items?.[index]?.unit_price && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.unit_price?.message}
                              </p>
                            )}
                          </TableCell>
                          <TableCell className="text-right font-medium">
                            {lineTotal}
                          </TableCell>
                          <TableCell>
                            <Input
                              placeholder="Remarks"
                              {...register(`items.${index}.remarks`)}
                            />
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-0.5">
                              <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                disabled={index === 0}
                                onClick={() => swap(index, index - 1)}
                              >
                                <ArrowUp className="size-3.5" />
                              </Button>
                              <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                disabled={index === fields.length - 1}
                                onClick={() => swap(index, index + 1)}
                              >
                                <ArrowDown className="size-3.5" />
                              </Button>
                              <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => remove(index)}
                              >
                                <Trash2 className="size-4 text-destructive" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            )}

            {errors.items?.root && (
              <p className="mt-2 text-sm text-destructive">
                {errors.items.root.message}
              </p>
            )}
            {errors.items?.message && (
              <p className="mt-2 text-sm text-destructive">
                {errors.items.message}
              </p>
            )}

            {fields.length > 0 && (
              <div className="mt-4 space-y-1 border-t pt-4 text-right text-sm">
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span className="font-medium">{totals.subtotal}</span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Discount</span>
                  <span className="font-medium">-{totals.discountAmount}</span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Tax</span>
                  <span className="font-medium">{totals.taxAmount}</span>
                </div>
                <div className="flex justify-between gap-4 border-t pt-1">
                  <span className="font-semibold">Grand Total</span>
                  <span className="font-semibold">{totals.grandTotal}</span>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/sales-orders")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Sales Order" : "Create Sales Order"}
          </Button>
        </div>
      </div>

      <ImportOrderBookingDialog
        open={importDialogOpen}
        onOpenChange={setImportDialogOpen}
        customerId={selectedCustomer?.id ?? null}
        onImport={handleImportItems}
      />
    </form>
  );
}
