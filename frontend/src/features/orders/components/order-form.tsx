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
import { useOrderForm as useOrderFormMutation } from "@/features/orders/hooks/use-order-form";
import { ImportQuotationDialog } from "@/features/orders/components/import-quotation-dialog";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Customer } from "@/types/customer";

const itemSchema = z.object({
  quotation_item_id: z.string().optional(),
  quotation_id: z.string().optional(),
  source_type: z.enum(["manual", "quotation"]),
  description: z.string().min(1, "Description is required"),
  unit: z.string().min(1, "Unit is required"),
  ordered_quantity: z.string().min(1, "Quantity is required"),
  unit_price: z.string().min(1, "Unit price is required"),
  discount_percentage: z.string().optional().or(z.literal("")),
  tax_percentage: z.string().optional().or(z.literal("")),
  remarks: z.string().optional().or(z.literal("")),
  quoted_quantity: z.string().optional(),
  quotation_serial: z.string().optional(),
});

const orderSchema = z.object({
  customer_id: z.string().min(1, "Customer is required"),
  order_date: z.string().min(1, "Order date is required"),
  expected_delivery_date: z.string().optional().or(z.literal("")),
  reference_notes: z.string().optional().or(z.literal("")),
  notes: z.string().optional().or(z.literal("")),
  items: z.array(itemSchema).min(1, "At least one item is required"),
});

export type OrderFormValues = z.infer<typeof orderSchema>;

interface OrderFormProps {
  defaultValues?: Partial<OrderFormValues>;
  id?: number;
  customer?: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null;
}

export function OrderForm({
  defaultValues,
  id,
  customer: initialCustomer,
}: OrderFormProps) {
  const router = useRouter();
  const orderMutation = useOrderFormMutation({ id });
  const [customerSearch, setCustomerSearch] = useState("");
  const [customerDropdownOpen, setCustomerDropdownOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null>(null);

  const { data: customers = [] } = useCustomerSearch(customerSearch);

  const form = useForm<OrderFormValues>({
    resolver: zodResolver(orderSchema),
    defaultValues: {
      order_date: new Date().toISOString().split("T")[0],
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

  const [importDialogOpen, setImportDialogOpen] = useState(false);

  const { fields, append, remove, swap } = useFieldArray({ control, name: "items" });

  useEffect(() => {
    if (defaultValues) {
      reset({
        ...defaultValues,
        items: defaultValues.items ?? [],
      });
    }
  }, [defaultValues, reset]);

  useEffect(() => {
    if (defaultValues?.order_date) {
      setCustomerSearch("");
    }
  }, [defaultValues]);

  useEffect(() => {
    if (initialCustomer) {
      setSelectedCustomer(initialCustomer);
    }
  }, [initialCustomer]);

  const items = useWatch({ control, name: "items" });

  const totals = useMemo(() => {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;

    (items ?? []).forEach((item) => {
      const qty = parseFloat(item.ordered_quantity) || 0;
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

  const onSubmit = async (data: OrderFormValues) => {
    try {
      const payload = {
        ...data,
        items: data.items.map((item) => ({
          ...item,
          quotation_item_id: item.quotation_item_id || undefined,
          quotation_id: item.quotation_id || undefined,
          discount_percentage: item.discount_percentage || undefined,
          tax_percentage: item.tax_percentage || undefined,
          remarks: item.remarks || undefined,
          quoted_quantity: undefined,
          quotation_serial: undefined,
        })),
      };
      await orderMutation.mutateAsync(
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

  const addManualItem = () => {
    append({
      source_type: "manual",
      description: "",
      unit: "",
      ordered_quantity: "",
      unit_price: "",
      discount_percentage: "",
      tax_percentage: "",
      remarks: "",
    });
  };

  const handleImportItems = (importedItems: Array<{
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
  }>) => {
    importedItems.forEach((item) => {
      append({
        quotation_item_id: String(item.quotation_item_id),
        quotation_id: String(item.quotation_id),
        quotation_serial: item.quotation_serial,
        source_type: "quotation",
        description: item.description,
        unit: item.unit,
        ordered_quantity: item.ordered_quantity,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage,
        tax_percentage: item.tax_percentage,
        quoted_quantity: item.quoted_quantity,
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
          onClick={() => router.push("/orders")}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Orders
        </Button>
      </div>

      <div className="grid gap-6">
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Order Details</CardTitle>
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
                <Label htmlFor="order_date">Order Date *</Label>
                <input
                  id="order_date"
                  type="date"
                  {...register("order_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.order_date && (
                  <p className="text-sm text-destructive">
                    {errors.order_date.message}
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
              <div className="flex items-center gap-2">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={addManualItem}
                  className="gap-1.5"
                >
                  <Plus className="size-4" />
                  Add Manual Item
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="gap-1.5"
                  disabled={!selectedCustomer}
                  onClick={() => setImportDialogOpen(true)}
                >
                  <FileText className="size-4" />
                  Import From Quotation
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {fields.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No items yet. Add a manual item or import from a quotation.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-20">Source</TableHead>
                      <TableHead className="w-36">Quotation</TableHead>
                      <TableHead className="min-w-48">Description</TableHead>
                      <TableHead className="w-20">Unit</TableHead>
                      <TableHead className="w-24">Quantity</TableHead>
                      <TableHead className="w-24">Unit Price</TableHead>
                      <TableHead className="w-24">Line Total</TableHead>
                      <TableHead className="min-w-32">Remarks</TableHead>
                      <TableHead className="w-12" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {fields.map((field, index) => {
                      const qty = parseFloat(items?.[index]?.ordered_quantity ?? "0") || 0;
                      const price = parseFloat(items?.[index]?.unit_price ?? "0") || 0;
                      const lineTotal = (qty * price).toFixed(2);

                      return (
                        <TableRow key={field.id}>
                          <TableCell>
                            <span className="text-xs font-medium uppercase text-muted-foreground">
                              {field.source_type}
                            </span>
                          </TableCell>
                          <TableCell>
                            {field.source_type === "quotation" ? (
                              <input type="hidden" {...register(`items.${index}.quotation_serial`)} />
                            ) : null}
                            <span className="text-xs text-muted-foreground">
                              {field.source_type === "quotation"
                                ? (items?.[index]?.quotation_serial ?? "-")
                                : "-"}
                            </span>
                          </TableCell>
                          <TableCell>
                            <input type="hidden" {...register(`items.${index}.source_type`)} />
                            <input type="hidden" {...register(`items.${index}.quotation_item_id`)} />
                            <input type="hidden" {...register(`items.${index}.quotation_id`)} />
                            <input type="hidden" {...register(`items.${index}.quoted_quantity`)} />
                            <input type="hidden" {...register(`items.${index}.quotation_serial`)} />
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
                          <TableCell>
                            <Input
                              type="number"
                              step="0.01"
                              min="0.01"
                              placeholder="0"
                              {...register(`items.${index}.ordered_quantity`)}
                            />
                            {errors.items?.[index]?.ordered_quantity && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.ordered_quantity?.message}
                              </p>
                            )}
                            {field.source_type === "quotation" &&
                              items?.[index]?.quoted_quantity &&
                              parseFloat(items[index]?.ordered_quantity ?? "0") <
                                parseFloat(items[index]?.quoted_quantity ?? "0") && (
                                <p className="text-xs text-amber-600">
                                  Qty below quoted ({items[index]?.quoted_quantity})
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
            onClick={() => router.push("/orders")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Order" : "Create Order"}
          </Button>
        </div>
      </div>

      <ImportQuotationDialog
        open={importDialogOpen}
        onOpenChange={setImportDialogOpen}
        customerId={selectedCustomer?.id ?? null}
        onImport={handleImportItems}
      />
    </form>
  );
}
