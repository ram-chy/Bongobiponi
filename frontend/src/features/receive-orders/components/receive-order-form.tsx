"use client";

import { useState, useMemo } from "react";
import { useForm, useFieldArray, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
import { Loader2, ArrowLeft, Plus, Trash2, Search } from "lucide-react";
import { useReceiveOrderForm } from "@/features/receive-orders/hooks/use-receive-order-form";
import { useCustomerSearch } from "@/hooks/use-customer-search";
import { supplierService } from "@/services/supplier-service";
import { bookService } from "@/services/book-service";
import { mapValidationErrors } from "@/lib/api-errors";
import type { ReceiveOrder } from "@/types/receive-order";
import type { Customer } from "@/types/customer";

const receiveOrderSchema = z.object({
  supplier_id: z.string().min(1, "Supplier is required"),
  customer_id: z.string().optional().or(z.literal("")),
  expected_delivery_date: z.string().min(1, "Expected delivery date is required"),
  reference_no: z.string().optional().or(z.literal("")),
  notes: z.string().optional().or(z.literal("")),
  items: z
    .array(
      z.object({
        book_id: z.string().min(1, "Book is required"),
        ordered_quantity: z.string().min(1, "Quantity is required").refine(
          (val) => parseInt(val) > 0,
          "Quantity must be at least 1"
        ),
        purchase_price: z.string().min(1, "Price is required").refine(
          (val) => parseFloat(val) >= 0,
          "Price must not be negative"
        ),
        discount_percentage: z.string().optional().or(z.literal("")),
        tax_percentage: z.string().optional().or(z.literal("")),
        remarks: z.string().optional().or(z.literal("")),
      })
    )
    .min(1, "At least one item is required"),
});

type ReceiveOrderFormData = z.infer<typeof receiveOrderSchema>;

interface ReceiveOrderFormProps {
  defaultValues?: Partial<ReceiveOrder>;
  id?: number;
}

export function ReceiveOrderForm({ defaultValues, id }: ReceiveOrderFormProps) {
  const router = useRouter();
  const receiveOrderMutation = useReceiveOrderForm({ id });
  const [customerSearch, setCustomerSearch] = useState("");
  const [customerDropdownOpen, setCustomerDropdownOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(
    defaultValues?.customer as Customer ?? null
  );

  const { data: customers = [] } = useCustomerSearch(customerSearch);

  const { data: suppliersData, isLoading: suppliersLoading } = useQuery({
    queryKey: ["/suppliers"],
    queryFn: async () => {
      const response = await supplierService.list({ per_page: 100 });
      return response.data.data;
    },
  });

  const { data: booksData, isLoading: booksLoading } = useQuery({
    queryKey: ["/books"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    watch,
    control,
    formState: { errors, isSubmitting },
  } = useForm<ReceiveOrderFormData>({
    resolver: zodResolver(receiveOrderSchema),
    defaultValues: {
      supplier_id: defaultValues?.supplier_id?.toString() ?? "",
      customer_id: defaultValues?.customer_id?.toString() ?? "",
      expected_delivery_date: defaultValues?.expected_delivery_date
        ? defaultValues.expected_delivery_date.split("T")[0]
        : "",
      reference_no: defaultValues?.reference_no ?? "",
      notes: defaultValues?.notes ?? "",
      items:
        defaultValues?.items?.map((item) => ({
          book_id: item.book_id.toString(),
          ordered_quantity: item.ordered_quantity.toString(),
          purchase_price: item.purchase_price.toString(),
          discount_percentage: item.discount_percentage?.toString() ?? "",
          tax_percentage: item.tax_percentage?.toString() ?? "",
          remarks: item.remarks ?? "",
        })) ?? [
          { book_id: "", ordered_quantity: "", purchase_price: "", discount_percentage: "", tax_percentage: "", remarks: "" },
        ],
    },
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: "items",
  });

  const items = useWatch({ control, name: "items" });

  const totals = useMemo(() => {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;

    (items ?? []).forEach((item) => {
      const qty = parseFloat(item.ordered_quantity) || 0;
      const price = parseFloat(item.purchase_price) || 0;
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

  const handleSelectCustomer = (customer: Customer) => {
    setSelectedCustomer(customer);
    setValue("customer_id", String(customer.id));
    setCustomerDropdownOpen(false);
    setCustomerSearch("");
  };

  const onSubmit = async (data: ReceiveOrderFormData) => {
    const payload = {
      supplier_id: parseInt(data.supplier_id),
      customer_id: data.customer_id ? parseInt(data.customer_id) : null,
      expected_delivery_date: data.expected_delivery_date,
      reference_no: data.reference_no || null,
      notes: data.notes || null,
      items: data.items.map((item) => ({
        book_id: parseInt(item.book_id),
        ordered_quantity: parseInt(item.ordered_quantity),
        purchase_price: parseFloat(item.purchase_price),
        discount_percentage: parseFloat(item.discount_percentage ?? "0") || 0,
        tax_percentage: parseFloat(item.tax_percentage ?? "0") || 0,
        remarks: item.remarks || null,
      })),
    };

    try {
      await receiveOrderMutation.mutateAsync(
        payload as unknown as Record<string, unknown>
      );
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  if (suppliersLoading || booksLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const suppliers = suppliersData ?? [];
  const books = booksData ?? [];

  const selectedSupplierId = watch("supplier_id");
  const selectedSupplier = suppliers.find(
    (s: { id: number }) => s.id.toString() === selectedSupplierId
  );

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/receive-orders")}
        >
          <ArrowLeft className="size-4" />
          Back to Receive Orders
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Receive Order Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="supplier_id">Supplier *</Label>
              <Select
                value={watch("supplier_id") || null}
                onValueChange={(value) => setValue("supplier_id", String(value ?? ""))}
                items={suppliers.map((supplier: { id: number; name: string; company_name: string }) => ({ value: supplier.id.toString(), label: supplier.company_name }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select supplier">
                    {selectedSupplier?.company_name}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent>
                  {suppliers.map((supplier: { id: number; name: string; company_name: string }) => (
                    <SelectItem key={supplier.id} value={supplier.id.toString()}>
                      {supplier.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.supplier_id && (
                <p className="text-sm text-destructive">
                  {errors.supplier_id.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label>Order Placer (Customer)</Label>
              {selectedCustomer ? (
                <div>
                  <p className="text-sm font-medium text-muted-foreground">
                    {selectedCustomer.company_name || selectedCustomer.name}
                  </p>
                  <button
                    type="button"
                    className="text-xs text-destructive hover:underline mt-1"
                    onClick={() => {
                      setSelectedCustomer(null);
                      setValue("customer_id", "");
                    }}
                  >
                    Remove
                  </button>
                </div>
              ) : (
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
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="expected_delivery_date">
                Expected Delivery Date *
              </Label>
              <Input
                id="expected_delivery_date"
                type="date"
                {...register("expected_delivery_date")}
              />
              {errors.expected_delivery_date && (
                <p className="text-sm text-destructive">
                  {errors.expected_delivery_date.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="reference_no">Reference No</Label>
              <Input id="reference_no" {...register("reference_no")} />
            </div>

            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="notes">Notes</Label>
              <Textarea id="notes" {...register("notes")} />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Items</CardTitle>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() =>
                append({
                  book_id: "",
                  ordered_quantity: "",
                  purchase_price: "",
                  discount_percentage: "",
                  tax_percentage: "",
                  remarks: "",
                })
              }
            >
              <Plus className="size-4" />
              Add Item
            </Button>
          </CardHeader>
          <CardContent>
            {errors.items && typeof errors.items.message === "string" && (
              <p className="text-sm text-destructive mb-4">
                {errors.items.message}
              </p>
            )}
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-[200px]">Book *</TableHead>
                    <TableHead className="w-[80px]">Qty *</TableHead>
                    <TableHead className="w-[110px]">Price *</TableHead>
                    <TableHead className="w-[80px]">Disc %</TableHead>
                    <TableHead className="w-[80px]">Tax %</TableHead>
                    <TableHead className="w-[110px] text-right">Line Total</TableHead>
                    <TableHead>Remarks</TableHead>
                    <TableHead className="w-[50px]" />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {fields.map((field, index) => {
                    const selectedBookId = watch(`items.${index}.book_id`);
                    const selectedBook = books.find(
                      (b: { id: number }) => b.id.toString() === selectedBookId
                    ) as { id: number; title: string; purchase_price?: number } | undefined;

                    const qty = parseFloat(items?.[index]?.ordered_quantity ?? "0") || 0;
                    const price = parseFloat(items?.[index]?.purchase_price ?? "0") || 0;
                    const discPct = parseFloat(items?.[index]?.discount_percentage ?? "0") || 0;
                    const taxPct = parseFloat(items?.[index]?.tax_percentage ?? "0") || 0;
                    const base = qty * price;
                    const disc = base * (discPct / 100);
                    const tax = (base - disc) * (taxPct / 100);
                    const lineTotal = (base - disc + tax).toFixed(2);

                    return (
                    <TableRow key={field.id}>
                      <TableCell>
                        <Select
                          value={selectedBookId || null}
                          onValueChange={(value) => {
                            setValue(`items.${index}.book_id`, String(value ?? ""));
                            const book = books.find(
                              (b: { id: number }) => b.id.toString() === value
                            ) as { id: number; title: string; purchase_price?: number } | undefined;
                            if (book?.purchase_price != null) {
                              setValue(`items.${index}.purchase_price`, String(book.purchase_price));
                            }
                          }}
                          items={books.map((book: { id: number; title: string }) => ({ value: book.id.toString(), label: book.title }))}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Select book">
                              {selectedBook?.title}
                            </SelectValue>
                          </SelectTrigger>
                          <SelectContent>
                            {books.map(
                              (book: { id: number; title: string }) => (
                                <SelectItem
                                  key={book.id}
                                  value={book.id.toString()}
                                >
                                  {book.title}
                                </SelectItem>
                              )
                            )}
                          </SelectContent>
                        </Select>
                        {errors.items?.[index]?.book_id && (
                          <p className="text-sm text-destructive mt-1">
                            {errors.items[index]?.book_id?.message}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          min="1"
                          {...register(`items.${index}.ordered_quantity`)}
                        />
                        {errors.items?.[index]?.ordered_quantity && (
                          <p className="text-sm text-destructive mt-1">
                            {errors.items[index]?.ordered_quantity?.message}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          min="0"
                          step="0.01"
                          readOnly
                          {...register(`items.${index}.purchase_price`)}
                        />
                        {errors.items?.[index]?.purchase_price && (
                          <p className="text-sm text-destructive mt-1">
                            {errors.items[index]?.purchase_price?.message}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          placeholder="0"
                          {...register(`items.${index}.discount_percentage`)}
                        />
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          placeholder="0"
                          {...register(`items.${index}.tax_percentage`)}
                        />
                      </TableCell>
                      <TableCell className="text-right font-medium">
                        {lineTotal}
                      </TableCell>
                      <TableCell>
                        <Input {...register(`items.${index}.remarks`)} />
                      </TableCell>
                      <TableCell>
                        {fields.length > 1 && (
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => remove(index)}
                          >
                            <Trash2 className="size-4 text-destructive" />
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>

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
            onClick={() => router.push("/receive-orders")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Receive Order" : "Create Receive Order"}
          </Button>
        </div>
      </div>
    </form>
  );
}
