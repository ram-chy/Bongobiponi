"use client";

import { useState, useEffect } from "react";
import { useForm, type UseFormReturn } from "react-hook-form";
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Loader2, ArrowLeft, Search } from "lucide-react";
import { useCustomerSearch } from "@/hooks/use-customer-search";
import { useQuotationForm } from "@/features/quotations/hooks/use-quotation-form";
import { QuotationItemsTable } from "@/features/quotations/components/quotation-items-table";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Customer } from "@/types/customer";

const itemSchema = z.object({
  description: z.string().min(1, "Description is required"),
  quantity: z.string().min(1, "Quantity is required"),
  unit: z.string().min(1, "Unit is required"),
  unit_price: z.string().min(1, "Unit price is required"),
  discount_percentage: z.string().optional().or(z.literal("")),
  tax_percentage: z.string().optional().or(z.literal("")),
  remarks: z.string().optional().or(z.literal("")),
});

const quotationSchema = z.object({
  customer_id: z.string().min(1, "Customer is required"),
  quotation_date: z.string().min(1, "Quotation date is required"),
  valid_until: z.string().min(1, "Valid until is required"),
  status: z.string().optional().or(z.literal("")),
  notes: z.string().optional().or(z.literal("")),
  items: z.array(itemSchema).min(1, "At least one item is required"),
});

export type QuotationFormValues = z.infer<typeof quotationSchema>;

interface QuotationFormProps {
  defaultValues?: Partial<QuotationFormValues>;
  id?: number;
  customer?: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null;
}

export function QuotationForm({
  defaultValues,
  id,
  customer: initialCustomer,
}: QuotationFormProps) {
  const router = useRouter();
  const quotationMutation = useQuotationForm({ id });
  const [customerSearch, setCustomerSearch] = useState("");
  const [customerDropdownOpen, setCustomerDropdownOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);

  const { data: customers = [] } = useCustomerSearch(customerSearch);

  const form = useForm<QuotationFormValues>({
    resolver: zodResolver(quotationSchema),
    defaultValues: {
      quotation_date: new Date().toISOString().split("T")[0],
      valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
      status: "DRAFT",
      notes: "",
      items: [
        {
          description: "",
          quantity: "",
          unit: "",
          unit_price: "",
          discount_percentage: "",
          tax_percentage: "",
          remarks: "",
        },
      ],
      ...defaultValues,
      ...(defaultValues?.status ? { status: defaultValues.status.toUpperCase() } : {}),
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

  useEffect(() => {
    if (defaultValues) {
      reset({
        ...defaultValues,
        status: defaultValues.status?.toUpperCase() ?? "DRAFT",
      });
    }
  }, [defaultValues, reset]);

  useEffect(() => {
    if (defaultValues?.customer_id) {
      setCustomerSearch("");
    }
  }, [defaultValues]);

  useEffect(() => {
    if (initialCustomer) {
      setSelectedCustomer(initialCustomer as Customer);
    }
  }, [initialCustomer]);

  const onSubmit = async (data: QuotationFormValues) => {
    try {
      const payload = {
        ...data,
        status: data.status?.toLowerCase(),
      };
      await quotationMutation.mutateAsync(
        payload as unknown as Record<string, unknown>
      );
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  const handleSelectCustomer = (customer: Customer) => {
    setSelectedCustomer(customer);
    setValue("customer_id", String(customer.id));
    setCustomerDropdownOpen(false);
    setCustomerSearch("");
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/quotations")}
        >
          <ArrowLeft className="size-4" />
          Back to Quotations
        </Button>
      </div>

      <div className="space-y-6">
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Customer Information</CardTitle>
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
                    <span className="font-medium">
                      {selectedCustomer.email}
                    </span>
                  </div>
                )}
                {selectedCustomer.gst_number && (
                  <div>
                    <span className="text-muted-foreground">GST: </span>
                    <span className="font-medium">
                      {selectedCustomer.gst_number}
                    </span>
                  </div>
                )}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Quotation Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="quotation_date">Quotation Date *</Label>
              <input
                id="quotation_date"
                type="date"
                {...register("quotation_date")}
                className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
              />
              {errors.quotation_date && (
                <p className="text-sm text-destructive">
                  {errors.quotation_date.message}
                </p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="valid_until">Valid Until *</Label>
              <input
                id="valid_until"
                type="date"
                {...register("valid_until")}
                className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
              />
              {errors.valid_until && (
                <p className="text-sm text-destructive">
                  {errors.valid_until.message}
                </p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <Select
                value={watch("status") || "DRAFT"}
                onValueChange={(v) => setValue("status", v ?? "DRAFT")}
                items={[
                  { value: "DRAFT", label: "DRAFT" },
                  { value: "SENT", label: "SENT" },
                  { value: "ACCEPTED", label: "ACCEPTED" },
                  { value: "REJECTED", label: "REJECTED" },
                  { value: "EXPIRED", label: "EXPIRED" },
                ]}
              >
                <SelectTrigger id="status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="DRAFT">DRAFT</SelectItem>
                  <SelectItem value="SENT">SENT</SelectItem>
                  <SelectItem value="ACCEPTED">ACCEPTED</SelectItem>
                  <SelectItem value="REJECTED">REJECTED</SelectItem>
                  <SelectItem value="EXPIRED">EXPIRED</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Items</CardTitle>
          </CardHeader>
          <CardContent>
            <QuotationItemsTable form={form as unknown as UseFormReturn} />
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
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <Textarea rows={4} {...register("notes")} placeholder="Additional notes..." />
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/quotations")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Quotation" : "Create Quotation"}
          </Button>
        </div>
      </div>
    </form>
  );
}
