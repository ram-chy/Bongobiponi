"use client";

import { useMemo } from "react";
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
import { Loader2, ArrowLeft, Plus, Trash2 } from "lucide-react";
import { usePurchaseForm } from "@/features/purchases/hooks/use-purchase-form";
import { supplierService } from "@/services/supplier-service";
import { publisherService } from "@/services/publisher-service";
import { bookService } from "@/services/book-service";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Purchase } from "@/types/purchase";

const purchaseSchema = z.object({
  supplier_id: z.string().min(1, "Supplier is required"),
  publisher_id: z.string().optional().or(z.literal("")),
  invoice_no: z.string().optional().or(z.literal("")),
  invoice_date: z.string().optional().or(z.literal("")),
  purchase_date: z.string().min(1, "Purchase date is required"),
  notes: z.string().optional().or(z.literal("")),
  items: z
    .array(
      z.object({
        book_id: z.string().min(1, "Book is required"),
        ordered_quantity: z.string().optional().or(z.literal("")),
        received_quantity: z.string().min(1, "Received quantity is required").refine(
          (val) => parseInt(val) > 0,
          "Received quantity must be at least 1"
        ),
        purchase_price: z.string().min(1, "Price is required").refine(
          (val) => parseFloat(val) >= 0,
          "Price must not be negative"
        ),
        remarks: z.string().optional().or(z.literal("")),
      })
    )
    .min(1, "At least one item is required"),
});

type PurchaseFormData = z.infer<typeof purchaseSchema>;

interface PurchaseFormProps {
  defaultValues?: Partial<Purchase>;
  id?: number;
}

export function PurchaseForm({ defaultValues, id }: PurchaseFormProps) {
  const router = useRouter();
  const purchaseMutation = usePurchaseForm({ id });

  const { data: suppliersData, isLoading: suppliersLoading } = useQuery({
    queryKey: ["/suppliers"],
    queryFn: async () => {
      const response = await supplierService.list({ per_page: 100 });
      return response.data.data;
    },
  });

  const { data: publishersData, isLoading: publishersLoading } = useQuery({
    queryKey: ["/publishers"],
    queryFn: async () => {
      const response = await publisherService.list({ per_page: 100 });
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
  } = useForm<PurchaseFormData>({
    resolver: zodResolver(purchaseSchema),
    defaultValues: {
      supplier_id: defaultValues?.supplier_id?.toString() ?? "",
      publisher_id: defaultValues?.publisher_id?.toString() ?? "",
      invoice_no: defaultValues?.invoice_no ?? "",
      invoice_date: defaultValues?.invoice_date
        ? defaultValues.invoice_date.split("T")[0]
        : "",
      purchase_date: defaultValues?.purchase_date
        ? defaultValues.purchase_date.split("T")[0]
        : "",
      notes: defaultValues?.notes ?? "",
      items:
        defaultValues?.items?.map((item) => ({
          book_id: item.book_id.toString(),
          ordered_quantity: item.ordered_quantity?.toString() ?? "",
          received_quantity: item.received_quantity.toString(),
          purchase_price: item.purchase_price.toString(),
          remarks: item.remarks ?? "",
        })) ?? [
          { book_id: "", ordered_quantity: "", received_quantity: "", purchase_price: "", remarks: "" },
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
      const qty = parseFloat(item.received_quantity) || 0;
      const price = parseFloat(item.purchase_price) || 0;
      subtotal += qty * price;
    });

    return {
      subtotal: subtotal.toFixed(2),
      grandTotal: subtotal.toFixed(2),
    };
  }, [items]);

  const onSubmit = async (data: PurchaseFormData) => {
    const payload = {
      supplier_id: parseInt(data.supplier_id),
      publisher_id: data.publisher_id ? parseInt(data.publisher_id) : null,
      invoice_no: data.invoice_no || null,
      invoice_date: data.invoice_date || null,
      purchase_date: data.purchase_date,
      notes: data.notes || null,
      items: data.items.map((item) => ({
        book_id: parseInt(item.book_id),
        ordered_quantity: parseInt(item.ordered_quantity || "0") || 0,
        received_quantity: parseInt(item.received_quantity),
        purchase_price: parseFloat(item.purchase_price),
        remarks: item.remarks || null,
      })),
    };

    try {
      await purchaseMutation.mutateAsync(
        payload as unknown as Record<string, unknown>
      );
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  if (suppliersLoading || publishersLoading || booksLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const suppliers = suppliersData ?? [];
  const publishers = publishersData ?? [];
  const books = booksData ?? [];

  const selectedSupplierId = watch("supplier_id");
  const selectedSupplier = suppliers.find(
    (s: { id: number }) => s.id.toString() === selectedSupplierId
  );

  const selectedPublisherId = watch("publisher_id");
  const selectedPublisher = publishers.find(
    (p: { id: number; name: string }) => p.id.toString() === selectedPublisherId
  );

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/purchases")}
        >
          <ArrowLeft className="size-4" />
          Back to Purchases
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Purchase Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="supplier_id">Supplier *</Label>
              <Select
                value={watch("supplier_id") || null}
                onValueChange={(value) => setValue("supplier_id", String(value ?? ""))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select supplier">
                    {selectedSupplier?.company_name}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent>
                  {suppliers.map((supplier: { id: number; company_name: string }) => (
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
              <Label htmlFor="publisher_id">Publisher</Label>
              <Select
                value={watch("publisher_id") || null}
                onValueChange={(value) => setValue("publisher_id", String(value ?? ""))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select publisher">
                    {selectedPublisher?.name}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent>
                  {publishers.map((publisher: { id: number; name: string }) => (
                    <SelectItem key={publisher.id} value={publisher.id.toString()}>
                      {publisher.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.publisher_id && (
                <p className="text-sm text-destructive">
                  {errors.publisher_id.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="invoice_no">Invoice No</Label>
              <Input id="invoice_no" {...register("invoice_no")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="purchase_date">Purchase Date *</Label>
              <Input id="purchase_date" type="date" {...register("purchase_date")} />
              {errors.purchase_date && (
                <p className="text-sm text-destructive">
                  {errors.purchase_date.message}
                </p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="invoice_date">Invoice Date</Label>
              <Input id="invoice_date" type="date" {...register("invoice_date")} />
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
                  received_quantity: "",
                  purchase_price: "",
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
                    <TableHead className="w-[80px]">Ordered Qty</TableHead>
                    <TableHead className="w-[100px]">Received Qty *</TableHead>
                    <TableHead className="w-[110px]">Price *</TableHead>
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
                    ) as { id: number; title: string } | undefined;

                    const qty = parseFloat(items?.[index]?.received_quantity ?? "0") || 0;
                    const price = parseFloat(items?.[index]?.purchase_price ?? "0") || 0;
                    const lineTotal = (qty * price).toFixed(2);

                    return (
                    <TableRow key={field.id}>
                      <TableCell>
                        <Select
                          value={selectedBookId || null}
                          onValueChange={(value) => {
                            setValue(`items.${index}.book_id`, String(value ?? ""));
                            const book = books.find(
                              (b: { id: number }) => b.id.toString() === value
                            ) as { id: number; title: string; purchase_price: number } | undefined;
                            if (book) {
                              setValue(`items.${index}.purchase_price`, book.purchase_price.toString());
                              if (!items?.[index]?.ordered_quantity) {
                                setValue(`items.${index}.ordered_quantity`, "0");
                              }
                            }
                          }}
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
                          min="0"
                          {...register(`items.${index}.ordered_quantity`)}
                        />
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          min="1"
                          {...register(`items.${index}.received_quantity`)}
                        />
                        {errors.items?.[index]?.received_quantity && (
                          <p className="text-sm text-destructive mt-1">
                            {errors.items[index]?.received_quantity?.message}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          min="0"
                          step="0.01"
                          {...register(`items.${index}.purchase_price`)}
                        />
                        {errors.items?.[index]?.purchase_price && (
                          <p className="text-sm text-destructive mt-1">
                            {errors.items[index]?.purchase_price?.message}
                          </p>
                        )}
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
            onClick={() => router.push("/purchases")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Purchase" : "Create Purchase"}
          </Button>
        </div>
      </div>
    </form>
  );
}
