"use client";

import { useState, useEffect, useMemo, useCallback } from "react";
import { useForm, useFieldArray, useWatch } from "react-hook-form";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Loader2, ArrowLeft, Search, Trash2, Plus } from "lucide-react";
import { useInvoiceForm as useInvoiceFormMutation } from "@/features/invoices/hooks/use-invoice-form";
import { useInvoiceableItems } from "@/features/invoices/hooks/use-invoiceable-items";
import { deliveryChallanService } from "@/services/delivery-challan-service";
import { mapValidationErrors } from "@/lib/api-errors";
import { useQuery } from "@tanstack/react-query";
import type { InvoiceableItem } from "@/types/invoice";

const itemSchema = z.object({
  delivery_challan_item_id: z.string().min(1, "Required"),
  delivery_challan_id: z.string().min(1, "Required"),
  delivery_challan_serial: z.string().optional(),
  item_description: z.string().min(1, "Description is required"),
  unit: z.string().min(1, "Unit is required"),
  delivered_quantity: z.string(),
  already_invoiced: z.string(),
  available_for_invoicing: z.string(),
  invoiced_quantity: z.string().min(1, "Invoice qty is required"),
  unit_price: z.string().min(1, "Unit price is required"),
  remarks: z.string().optional().or(z.literal("")),
});

const invoiceSchema = z.object({
  customer_id: z.string().min(1, "Customer is required"),
  invoice_date: z.string().min(1, "Invoice date is required"),
  due_date: z.string().min(1, "Due date is required"),
  billing_address: z.string().min(1, "Billing address is required"),
  status: z.enum(["draft", "issued"]),
  remarks: z.string().optional().or(z.literal("")),
  items: z.array(itemSchema).min(1, "At least one item is required"),
});

export type InvoiceFormValues = z.infer<typeof invoiceSchema>;

interface InvoiceFormProps {
  defaultValues?: Partial<InvoiceFormValues>;
  id?: number;
}

export function InvoiceForm({ defaultValues, id }: InvoiceFormProps) {
  const router = useRouter();
  const invoiceMutation = useInvoiceFormMutation({ id });

  const [dcSearch, setDcSearch] = useState("");
  const [dcDropdownOpen, setDcDropdownOpen] = useState(false);
  const [selectedDcId, setSelectedDcId] = useState<number | null>(null);

  const { data: dcResults = [] } = useQuery({
    queryKey: ["/delivery-challans", "search", dcSearch],
    queryFn: async () => {
      const response = await deliveryChallanService.list({
        search: dcSearch || undefined,
        per_page: 10,
      });
      return response.data.data ?? [];
    },
    enabled: dcDropdownOpen,
  });

  const { data: invoiceableItems = [] } = useInvoiceableItems(selectedDcId);

  const form = useForm<InvoiceFormValues>({
    resolver: zodResolver(invoiceSchema),
    defaultValues: {
      invoice_date: new Date().toISOString().split("T")[0],
      due_date: new Date().toISOString().split("T")[0],
      billing_address: "",
      status: "draft",
      remarks: "",
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

  const { fields, append, remove } = useFieldArray({ control, name: "items" });
  const items = useWatch({ control, name: "items" });
  const currentStatus = watch("status");

  useEffect(() => {
    if (defaultValues) {
      reset({
        ...defaultValues,
        items: defaultValues.items ?? [],
      });
    }
  }, [defaultValues, reset]);

  const handleSelectDc = useCallback((dc: { id: number; delivery_challan_serial: string; customer?: { id: number; company_name?: string | null; name?: string; billing_address?: string } | null }) => {
    setSelectedDcId(dc.id);
    if (dc.customer) {
      setValue("customer_id", String(dc.customer.id));
      setValue("billing_address", dc.customer.billing_address || "");
    }
    setDcDropdownOpen(false);
    setDcSearch("");
  }, [setValue]);

  const handleImportItems = useCallback(() => {
    invoiceableItems.forEach((item: InvoiceableItem) => {
      const alreadyExists = fields.some(
        (f) => f.delivery_challan_item_id === String(item.delivery_challan_item_id)
      );
      if (alreadyExists) return;

      append({
        delivery_challan_item_id: String(item.delivery_challan_item_id),
        delivery_challan_id: String(item.delivery_challan_id),
        delivery_challan_serial: item.delivery_challan_serial,
        item_description: item.item_description,
        unit: item.unit,
        delivered_quantity: String(item.delivered_quantity),
        already_invoiced: String(item.already_invoiced),
        available_for_invoicing: String(item.available_for_invoicing),
        invoiced_quantity: String(item.available_for_invoicing),
        unit_price: String(item.unit_price),
        remarks: "",
      });
    });
  }, [invoiceableItems, fields, append]);

  useEffect(() => {
    if (invoiceableItems.length > 0 && selectedDcId) {
      handleImportItems();
    }
  }, [invoiceableItems, selectedDcId, handleImportItems]);

  const totals = useMemo(() => {
    let subtotal = 0;
    let totalQty = 0;
    (items ?? []).forEach((item) => {
      const qty = parseFloat(item.invoiced_quantity) || 0;
      const price = parseFloat(item.unit_price) || 0;
      subtotal += qty * price;
      totalQty += qty;
    });
    const roundOff = Math.round(subtotal) - subtotal;
    return {
      itemCount: (items ?? []).length,
      totalQuantity: totalQty.toFixed(2),
      subtotal: subtotal.toFixed(2),
      roundOff: roundOff.toFixed(2),
      grandTotal: (subtotal + roundOff).toFixed(2),
    };
  }, [items]);

  const onSubmit = async (data: InvoiceFormValues) => {
    try {
      const payload = {
        customer_id: data.customer_id,
        invoice_date: data.invoice_date,
        due_date: data.due_date,
        billing_address: data.billing_address,
        status: data.status,
        remarks: data.remarks || undefined,
        items: data.items.map((item) => ({
          delivery_challan_item_id: item.delivery_challan_item_id,
          invoiced_quantity: item.invoiced_quantity,
          unit_price: item.unit_price,
          item_description: item.item_description,
          unit: item.unit,
          remarks: item.remarks || undefined,
        })),
      };
      await invoiceMutation.mutateAsync(payload as unknown as Record<string, unknown>);
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/invoices")}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Invoices
        </Button>
      </div>

      <div className="grid gap-6">
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Invoice Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Delivery Challan</Label>
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search delivery challan by serial..."
                  className="pl-8"
                  value={dcSearch}
                  onChange={(e) => {
                    setDcSearch(e.target.value);
                    setDcDropdownOpen(true);
                  }}
                  onFocus={() => setDcDropdownOpen(true)}
                />
                {dcDropdownOpen && dcResults.length > 0 && (
                  <div className="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-popover p-1 shadow-md">
                    {dcResults.map((dc: { id: number; delivery_challan_serial: string; customer?: { id: number; company_name?: string | null; name?: string } | null }) => (
                      <button
                        key={dc.id}
                        type="button"
                        className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                        onClick={() => handleSelectDc(dc)}
                      >
                        <div>
                          <p className="font-medium">
                            {dc.delivery_challan_serial}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {dc.customer?.company_name || dc.customer?.name || "-"}
                          </p>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="invoice_date">Invoice Date *</Label>
                <input
                  id="invoice_date"
                  type="date"
                  {...register("invoice_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.invoice_date && (
                  <p className="text-sm text-destructive">{errors.invoice_date.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="due_date">Due Date *</Label>
                <input
                  id="due_date"
                  type="date"
                  {...register("due_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.due_date && (
                  <p className="text-sm text-destructive">{errors.due_date.message}</p>
                )}
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="billing_address">Billing Address *</Label>
              <Textarea
                id="billing_address"
                rows={2}
                {...register("billing_address")}
                placeholder="Enter billing address"
              />
              {errors.billing_address && (
                <p className="text-sm text-destructive">{errors.billing_address.message}</p>
              )}
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
                onClick={() => {
                  setDcSearch("");
                  setDcDropdownOpen(true);
                }}
              >
                <Plus className="size-4" />
                Add Items from DC
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {fields.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No items yet. Search and select a Delivery Challan to add invoiceable items.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>DC Serial</TableHead>
                      <TableHead className="min-w-36">Description</TableHead>
                      <TableHead className="w-14">Unit</TableHead>
                      <TableHead className="w-18 text-right">Delivered</TableHead>
                      <TableHead className="w-18 text-right">Invoiced</TableHead>
                      <TableHead className="w-18 text-right">Available</TableHead>
                      <TableHead className="w-20">Invoice Qty</TableHead>
                      <TableHead className="w-18 text-right">Unit Price</TableHead>
                      <TableHead className="w-20" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {fields.map((field, index) => {
                      const available = parseFloat(items?.[index]?.available_for_invoicing ?? "0") || 0;
                      const invQty = parseFloat(items?.[index]?.invoiced_quantity ?? "0") || 0;
                      const unitPrice = parseFloat(items?.[index]?.unit_price ?? "0") || 0;
                      const exceedsAvailable = invQty > available;

                      return (
                        <TableRow key={field.id}>
                          <TableCell>
                            <input type="hidden" {...register(`items.${index}.delivery_challan_item_id`)} />
                            <input type="hidden" {...register(`items.${index}.delivery_challan_id`)} />
                            <input type="hidden" {...register(`items.${index}.delivery_challan_serial`)} />
                            <input type="hidden" {...register(`items.${index}.delivered_quantity`)} />
                            <input type="hidden" {...register(`items.${index}.already_invoiced`)} />
                            <input type="hidden" {...register(`items.${index}.available_for_invoicing`)} />
                            <span className="text-xs text-muted-foreground">
                              {items?.[index]?.delivery_challan_serial ?? "-"}
                            </span>
                          </TableCell>
                          <TableCell>
                            <Input
                              placeholder="Description"
                              {...register(`items.${index}.item_description`)}
                            />
                            {errors.items?.[index]?.item_description && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.item_description?.message}
                              </p>
                            )}
                          </TableCell>
                          <TableCell>
                            <Input
                              placeholder="Unit"
                              {...register(`items.${index}.unit`)}
                            />
                          </TableCell>
                          <TableCell className="text-right text-sm">
                            {items?.[index]?.delivered_quantity ?? "-"}
                          </TableCell>
                          <TableCell className="text-right text-sm">
                            {items?.[index]?.already_invoiced ?? "-"}
                          </TableCell>
                          <TableCell className="text-right text-sm font-medium">
                            {items?.[index]?.available_for_invoicing ?? "-"}
                          </TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              step="0.01"
                              min="0.01"
                              max={available}
                              placeholder="0"
                              className={exceedsAvailable ? "border-amber-500" : ""}
                              {...register(`items.${index}.invoiced_quantity`)}
                            />
                            {errors.items?.[index]?.invoiced_quantity && (
                              <p className="text-xs text-destructive">
                                {errors.items[index]?.invoiced_quantity?.message}
                              </p>
                            )}
                            {exceedsAvailable && (
                              <p className="text-xs text-amber-600">
                                Max: {available}
                              </p>
                            )}
                          </TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              step="0.01"
                              min="0"
                              placeholder="0"
                              {...register(`items.${index}.unit_price`)}
                            />
                          </TableCell>
                          <TableCell>
                            <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              onClick={() => remove(index)}
                            >
                              <Trash2 className="size-4 text-destructive" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            )}

            {errors.items?.root && (
              <p className="mt-2 text-sm text-destructive">{errors.items.root.message}</p>
            )}
            {errors.items?.message && (
              <p className="mt-2 text-sm text-destructive">{errors.items.message}</p>
            )}

            {fields.length > 0 && (
              <div className="mt-4 space-y-1 border-t pt-4 text-right text-sm">
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Total Items</span>
                  <span className="font-medium">{totals.itemCount}</span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Total Quantity</span>
                  <span className="font-medium">{totals.totalQuantity}</span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span className="font-medium">{totals.subtotal}</span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Round Off</span>
                  <span className="font-medium">{totals.roundOff}</span>
                </div>
                <div className="flex justify-between gap-4 border-t pt-1 font-medium">
                  <span>Grand Total</span>
                  <span>{totals.grandTotal}</span>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Status</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <Label htmlFor="status">Invoice Status</Label>
              <Select
                value={currentStatus}
                onValueChange={(v) => setValue("status", v as "draft" | "issued")}
                items={[
                  { value: "draft", label: "Draft" },
                  { value: "issued", label: "Issued" },
                ]}
              >
                <SelectTrigger id="status" className="w-full">
                  <SelectValue placeholder="Select status...">
                    {currentStatus ? currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1) : "Select..."}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="draft">Draft</SelectItem>
                  <SelectItem value="issued">Issued</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Remarks</CardTitle>
          </CardHeader>
          <CardContent>
            <Textarea rows={3} {...register("remarks")} placeholder="Additional notes..." />
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/invoices")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Invoice" : "Create Invoice"}
          </Button>
        </div>
      </div>
    </form>
  );
}
