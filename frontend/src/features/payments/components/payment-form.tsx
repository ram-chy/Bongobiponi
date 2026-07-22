"use client";

import { useState, useEffect, useMemo, useCallback, useRef } from "react";
import { useForm, useWatch } from "react-hook-form";
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
import { Loader2, ArrowLeft, Search, Check } from "lucide-react";
import { useCustomerSearch } from "@/hooks/use-customer-search";
import { usePaymentForm as usePaymentFormMutation } from "@/features/payments/hooks/use-payment-form";
import { useCustomerDueInvoices } from "@/features/payments/hooks/use-customer-due-invoices";
import { mapValidationErrors } from "@/lib/api-errors";
import type { DueInvoice } from "@/types/payment";
import type { Customer } from "@/types/customer";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

const paymentItemSchema = z.object({
  invoice_id: z.string().min(1, "Required"),
  paid_amount: z.string().min(1, "Amount is required"),
});

const paymentSchema = z.object({
  customer_id: z.string().min(1, "Customer is required"),
  payment_date: z.string().min(1, "Payment date is required"),
  payment_method: z.string().min(1, "Payment method is required"),
  reference_no: z.string().optional().or(z.literal("")),
  remarks: z.string().optional().or(z.literal("")),
  items: z.array(paymentItemSchema).min(1, "Select at least one invoice"),
});

export type PaymentFormValues = z.infer<typeof paymentSchema>;

interface PaymentFormProps {
  defaultValues?: Partial<PaymentFormValues>;
  id?: number;
  customer?: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null;
}

export function PaymentForm({ defaultValues, id, customer: initialCustomer }: PaymentFormProps) {
  const router = useRouter();
  const paymentMutation = usePaymentFormMutation({ id });

  const [customerSearch, setCustomerSearch] = useState("");
  const [customerDropdownOpen, setCustomerDropdownOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null>(null);
  const [selectedInvoiceIds, setSelectedInvoiceIds] = useState<Set<number>>(
    () =>
      defaultValues?.items
        ? new Set(defaultValues.items.map((item) => Number(item.invoice_id)))
        : new Set()
  );

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    watch,
    control,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<PaymentFormValues>({
    resolver: zodResolver(paymentSchema),
    defaultValues: {
      payment_date: new Date().toISOString().split("T")[0],
      payment_method: "",
      reference_no: "",
      remarks: "",
      items: [],
      ...defaultValues,
    },
  });

  const { data: customers = [] } = useCustomerSearch(customerSearch);

  const watchCustomerId = useWatch({ control, name: "customer_id" });
  const initialCustomerIdRef = useRef(watchCustomerId);
  const { data: dueInvoices = [] } = useCustomerDueInvoices(
    watchCustomerId ? Number(watchCustomerId) : null
  );

  useEffect(() => {
    if (watchCustomerId && watchCustomerId !== initialCustomerIdRef.current) {
      setSelectedInvoiceIds(new Set());
      setValue("items", []);
    }
  }, [watchCustomerId]);

  useEffect(() => {
    if (initialCustomer) {
      setSelectedCustomer(initialCustomer);
    }
  }, [initialCustomer]);

  useEffect(() => {
    if (defaultValues) {
      reset(defaultValues);
    }
  }, [defaultValues, reset]);

  const items = useWatch({ control, name: "items" });

  const toggleInvoice = useCallback((invoice: DueInvoice) => {
    const invoiceId = invoice.id;
    const newSet = new Set(selectedInvoiceIds);
    const currentItems = watch("items") ?? [];

    if (newSet.has(invoiceId)) {
      newSet.delete(invoiceId);
      setValue(
        "items",
        currentItems.filter((item) => Number(item.invoice_id) !== invoiceId)
      );
    } else {
      newSet.add(invoiceId);
      setValue("items", [
        ...currentItems,
        { invoice_id: String(invoiceId), paid_amount: String(invoice.due_amount) },
      ]);
    }

    setSelectedInvoiceIds(newSet);
  }, [selectedInvoiceIds]);

  const totalReceived = useMemo(() => {
    return (items ?? []).reduce((sum, item) => {
      return sum + (parseFloat(item.paid_amount) || 0);
    }, 0);
  }, [items]);

  const handleSelectCustomer = useCallback((customer: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number">) => {
    setSelectedCustomer(customer);
    setValue("customer_id", String(customer.id));
    setCustomerDropdownOpen(false);
    setCustomerSearch("");
  }, [setValue]);

  const onSubmit = async (data: PaymentFormValues) => {
    try {
      const payload = {
        customer_id: data.customer_id,
        payment_date: data.payment_date,
        payment_method: data.payment_method,
        reference_no: data.reference_no || undefined,
        remarks: data.remarks || undefined,
        items: data.items.map((item) => ({
          invoice_id: item.invoice_id,
          paid_amount: item.paid_amount,
        })),
      };
      await paymentMutation.mutateAsync(payload as Record<string, unknown>);
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
          onClick={() => router.push("/payments")}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Payments
        </Button>
      </div>

      <div className="grid gap-6">
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Customer</CardTitle>
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
                  <p className="text-sm text-destructive">{errors.customer_id.message}</p>
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

            {watchCustomerId && dueInvoices.length === 0 && (
              <p className="text-sm text-muted-foreground">
                No unpaid or partially paid invoices found for this customer.
              </p>
            )}
          </CardContent>
        </Card>

        {watchCustomerId && dueInvoices.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Select Invoices</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto rounded-lg border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-10" />
                      <TableHead>Invoice No</TableHead>
                      <TableHead>Date</TableHead>
                      <TableHead className="text-right">Invoice Amount</TableHead>
                      <TableHead className="text-right">Paid</TableHead>
                      <TableHead className="text-right">Due</TableHead>
                      <TableHead className="text-right">Paying</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {dueInvoices.map((invoice) => {
                      const isSelected = selectedInvoiceIds.has(invoice.id);
                      const itemIndex = (items ?? []).findIndex(
                        (i) => Number(i.invoice_id) === invoice.id
                      );

                      return (
                        <TableRow
                          key={invoice.id}
                          className={isSelected ? "bg-muted/30" : ""}
                        >
                          <TableCell>
                            <button
                              type="button"
                              className="flex size-5 items-center justify-center rounded border"
                              onClick={() => toggleInvoice(invoice)}
                            >
                              {isSelected && <Check className="size-3" />}
                            </button>
                          </TableCell>
                          <TableCell className="font-medium">{invoice.serial}</TableCell>
                          <TableCell className="text-sm text-muted-foreground">
                            {fmtDate(invoice.invoice_date)}
                          </TableCell>
                          <TableCell className="text-right">
                            {invoice.grand_total.toFixed(2)}
                          </TableCell>
                          <TableCell className="text-right">
                            {invoice.paid_amount.toFixed(2)}
                          </TableCell>
                          <TableCell className="text-right font-medium">
                            {invoice.due_amount.toFixed(2)}
                          </TableCell>
                          <TableCell className="text-right">
                            {isSelected && itemIndex !== -1 ? (
                              <Input
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={invoice.due_amount}
                                className="h-8 w-24 text-right"
                                {...register(`items.${itemIndex}.paid_amount`)}
                              />
                            ) : (
                              <span className="text-sm text-muted-foreground">-</span>
                            )}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
              <input type="hidden" {...register("items")} />
              {errors.items?.root && (
                <p className="mt-2 text-sm text-destructive">{errors.items.root.message}</p>
              )}
              {errors.items?.message && (
                <p className="mt-2 text-sm text-destructive">{errors.items.message}</p>
              )}
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Payment Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="payment_date">Payment Date *</Label>
                <input
                  id="payment_date"
                  type="date"
                  {...register("payment_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.payment_date && (
                  <p className="text-sm text-destructive">{errors.payment_date.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="payment_method">Payment Method *</Label>
                <Select
                  value={watch("payment_method") || null}
                   onValueChange={(v) => setValue("payment_method", v ?? "")}
                  items={[
                    { value: "Cash", label: "Cash" },
                    { value: "Bank Transfer", label: "Bank Transfer" },
                    { value: "UPI", label: "UPI" },
                    { value: "Cheque", label: "Cheque" },
                  ]}
                >
                  <SelectTrigger id="payment_method">
                    <SelectValue placeholder="Select method..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Cash">Cash</SelectItem>
                    <SelectItem value="Bank Transfer">Bank Transfer</SelectItem>
                    <SelectItem value="UPI">UPI</SelectItem>
                    <SelectItem value="Cheque">Cheque</SelectItem>
                  </SelectContent>
                </Select>
                {errors.payment_method && (
                  <p className="text-sm text-destructive">{errors.payment_method.message}</p>
                )}
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="reference_no">Reference No</Label>
              <Input
                id="reference_no"
                placeholder="Cheque / Transaction ref..."
                {...register("reference_no")}
              />
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

        {selectedInvoiceIds.size > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Summary</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-1 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Invoices</span>
                  <span className="font-medium">{selectedInvoiceIds.size}</span>
                </div>
                <div className="flex justify-between border-t pt-1 font-medium">
                  <span>Total Received</span>
                  <span>{totalReceived.toFixed(2)}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/payments")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Payment" : "Create Payment"}
          </Button>
        </div>
      </div>
    </form>
  );
}
