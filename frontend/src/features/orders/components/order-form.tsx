"use client";

import { useState, useEffect, useMemo, useCallback } from "react";
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
import { Loader2, ArrowLeft, Search, Plus, Trash2 } from "lucide-react";
import { useCustomerSearch } from "@/hooks/use-customer-search";
import { useOrderForm as useOrderFormMutation } from "@/features/orders/hooks/use-order-form";
import { bookService } from "@/services/book-service";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Book } from "@/types/book";
import type { Customer } from "@/types/customer";

const itemSchema = z.object({
  source_type: z.string(),
  book_id: z.string().min(1, "Book is required"),
  description: z.string().min(1, "Description is required"),
  unit: z.string().min(1, "Unit is required"),
  ordered_quantity: z.string().min(1, "Quantity is required"),
  unit_price: z.string().min(1, "Unit price is required"),
  discount_percentage: z.string().optional().or(z.literal("")),
  tax_percentage: z.string().optional().or(z.literal("")),
  remarks: z.string().optional().or(z.literal("")),
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

  const { data: booksData, isLoading: booksLoading } = useQuery<Book[]>({
    queryKey: ["/books"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data as Book[];
    },
  });

  const form = useForm<OrderFormValues>({
    resolver: zodResolver(orderSchema),
    defaultValues: {
      order_date: new Date().toISOString().split("T")[0],
      expected_delivery_date: "",
      reference_notes: "",
      notes: "",
      items: [
        {
          source_type: "manual",
          book_id: "",
          description: "",
          unit: "",
          ordered_quantity: "",
          unit_price: "",
          discount_percentage: "",
          tax_percentage: "",
          remarks: "",
        },
      ],
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

  const backfillBookIds = useCallback(() => {
    if (!booksData) return;

    form.getValues("items").forEach((item, index) => {
      if (!item.book_id && item.description) {
        const match = booksData.find((book) => book.title === item.description);
        if (match) {
          setValue(`items.${index}.book_id`, match.id.toString());
        }
      }
    });
  }, [booksData, form, setValue]);

  useEffect(() => {
    backfillBookIds();
  }, [backfillBookIds]);

  useEffect(() => {
    if (defaultValues) {
      reset({
        ...defaultValues,
        items: defaultValues.items ?? [],
      });
      backfillBookIds();
    }
  }, [defaultValues, reset, backfillBookIds]);

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
          source_type: item.source_type,
          description: item.description,
          unit: item.unit,
          ordered_quantity: item.ordered_quantity,
          unit_price: item.unit_price,
          discount_percentage: item.discount_percentage || undefined,
          tax_percentage: item.tax_percentage || undefined,
          remarks: item.remarks || undefined,
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

  if (booksLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const books = booksData ?? [];

  const addManualItem = () => {
    append({
      source_type: "manual",
      book_id: "",
      description: "",
      unit: "",
      ordered_quantity: "",
      unit_price: "",
      discount_percentage: "",
      tax_percentage: "",
      remarks: "",
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
                  Add Item
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {fields.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No items yet. Add an item to get started.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-[200px]">Book *</TableHead>
                      <TableHead className="w-[80px]">Quantity *</TableHead>
                      <TableHead className="w-[110px]">Unit Price *</TableHead>
                      <TableHead className="w-[110px] text-right">Line Total</TableHead>
                      <TableHead>Remarks</TableHead>
                      <TableHead className="w-[50px]" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {fields.map((field, index) => {
                      const itemDescription = items?.[index]?.description ?? "";
                      const selectedBookId =
                        watch(`items.${index}.book_id`) ||
                        books.find((book) => book.title === itemDescription)?.id.toString() ||
                        "";
                      const selectedBook = books.find(
                        (book) => book.id.toString() === selectedBookId
                      );

                      const qty = parseFloat(items?.[index]?.ordered_quantity ?? "0") || 0;
                      const price = parseFloat(items?.[index]?.unit_price ?? "0") || 0;
                      const lineTotal = (qty * price).toFixed(2);

                      return (
                        <TableRow key={field.id}>
                          <TableCell>
                            <input type="hidden" {...register(`items.${index}.source_type`)} />
                            <input type="hidden" {...register(`items.${index}.description`)} />
                            <input type="hidden" {...register(`items.${index}.unit`)} />
                            <Select
                              value={selectedBookId || null}
                              onValueChange={(value) => {
                                setValue(`items.${index}.book_id`, String(value ?? ""));
                                const book = books.find(
                                  (b) => b.id.toString() === value
                                ) as Book | undefined;
                                if (book) {
                                  setValue(`items.${index}.description`, book.title);
                                  setValue(`items.${index}.unit`, "pcs");
                                  setValue(`items.${index}.unit_price`, book.selling_price.toString());
                                }
                              }}
                            >
                              <SelectTrigger>
                                <SelectValue placeholder="Select book">
                                  {selectedBook?.title}
                                </SelectValue>
                              </SelectTrigger>
                              <SelectContent>
                                {books.map((book) => (
                                  <SelectItem
                                    key={book.id}
                                    value={book.id.toString()}
                                  >
                                    {book.title}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                            {errors.items?.[index]?.book_id && (
                              <p className="mt-1 text-xs text-destructive">
                                {errors.items[index]?.book_id?.message}
                              </p>
                            )}
                          </TableCell>
                          <TableCell>
                            <Input
                              type="number"
                              min="1"
                              placeholder="0"
                              {...register(`items.${index}.ordered_quantity`)}
                            />
                            {errors.items?.[index]?.ordered_quantity && (
                              <p className="mt-1 text-xs text-destructive">
                                {errors.items[index]?.ordered_quantity?.message}
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
                              <p className="mt-1 text-xs text-destructive">
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

    </form>
  );
}
