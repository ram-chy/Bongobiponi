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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import { Loader2, ArrowLeft, Search, Trash2, FileText, ArrowUp, ArrowDown } from "lucide-react";
import { useCustomerSearch } from "@/hooks/use-customer-search";
import { useDeliveryChallanForm as useDeliveryChallanFormMutation } from "@/features/delivery-challans/hooks/use-delivery-challan-form";
import { ImportOrderDialog } from "@/features/delivery-challans/components/import-order-dialog";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Customer } from "@/types/customer";

const itemSchema = z.object({
  order_booking_item_id: z.string().optional().or(z.literal("")),
  order_booking_id: z.string().optional().or(z.literal("")),
  source_type: z.string(),
  description: z.string().min(1, "Description is required"),
  unit: z.string().min(1, "Unit is required"),
  ordered_quantity: z.string(),
  delivery_quantity: z.string().min(1, "Delivery qty is required"),
  unit_price: z.string().min(1, "Unit price is required"),
  remarks: z.string().optional().or(z.literal("")),
  order_serial: z.string().optional(),
});

const deliveryChallanSchema = z.object({
  customer_id: z.string().min(1, "Customer is required"),
  delivery_date: z.string().min(1, "Delivery date is required"),
  delivery_address: z.string().min(1, "Delivery address is required"),
  transport_name: z.string().optional().or(z.literal("")),
  vehicle_number: z.string().optional().or(z.literal("")),
  driver_name: z.string().optional().or(z.literal("")),
  driver_mobile: z.string().optional().or(z.literal("")),
  lr_number: z.string().optional().or(z.literal("")),
  delivery_by: z.string().optional().or(z.literal("")),
  receiver_name: z.string().optional().or(z.literal("")),
  status: z.enum(["draft", "ready", "dispatched", "delivered", "cancelled"]),
  remarks: z.string().optional().or(z.literal("")),
  items: z.array(itemSchema).min(1, "At least one item is required"),
});

export type DeliveryChallanFormValues = z.infer<typeof deliveryChallanSchema>;

interface DeliveryChallanFormProps {
  defaultValues?: Partial<DeliveryChallanFormValues>;
  id?: number;
  customer?: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null;
}

export function DeliveryChallanForm({
  defaultValues,
  id,
  customer: initialCustomer,
}: DeliveryChallanFormProps) {
  const router = useRouter();
  const dcMutation = useDeliveryChallanFormMutation({ id });
  const [customerSearch, setCustomerSearch] = useState("");
  const [customerDropdownOpen, setCustomerDropdownOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number"> | null>(null);
  const [importDialogOpen, setImportDialogOpen] = useState(false);

  const { data: customers = [] } = useCustomerSearch(customerSearch);

  const form = useForm<DeliveryChallanFormValues>({
    resolver: zodResolver(deliveryChallanSchema),
    defaultValues: {
      delivery_date: new Date().toISOString().split("T")[0],
      delivery_address: "",
      transport_name: "",
      vehicle_number: "",
      driver_name: "",
      driver_mobile: "",
      lr_number: "",
      delivery_by: "",
      receiver_name: "",
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

  const { fields, append, remove, swap } = useFieldArray({ control, name: "items" });
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

  useEffect(() => {
    if (initialCustomer) {
      setSelectedCustomer(initialCustomer);
    }
  }, [initialCustomer]);

  const totals = useMemo(() => {
    let totalQty = 0;
    (items ?? []).forEach((item) => {
      totalQty += parseFloat(item.delivery_quantity) || 0;
    });
    return {
      itemCount: (items ?? []).length,
      totalQuantity: totalQty.toFixed(2),
    };
  }, [items]);

  const onSubmit = async (data: DeliveryChallanFormValues) => {
    try {
      const payload = {
        ...data,
        items: data.items.map((item) => ({
          order_booking_item_id: item.order_booking_item_id || undefined,
          description: item.description,
          unit: item.unit,
          ordered_quantity: item.ordered_quantity,
          unit_price: item.unit_price,
          delivered_quantity: item.delivery_quantity,
          remarks: item.remarks || undefined,
        })),
      };
      await dcMutation.mutateAsync(payload as unknown as Record<string, unknown>);
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  const handleSelectCustomer = (customer: Pick<Customer, "id" | "name" | "company_name" | "email" | "phone" | "billing_address" | "city" | "state" | "gst_number">) => {
    setSelectedCustomer(customer);
    setValue("customer_id", String(customer.id));
    setValue("delivery_address", customer.billing_address || "");
    setCustomerDropdownOpen(false);
    setCustomerSearch("");
  };

  const handleImportItems = (importedItems: Array<{
    order_booking_id: number;
    order_booking_item_id: number;
    description: string;
    unit: string;
    ordered_quantity: string;
    remaining_order_quantity: string;
    unit_price: string;
    delivery_quantity: string;
  }>) => {
    importedItems.forEach((item) => {
      append({
        order_booking_item_id: String(item.order_booking_item_id),
        order_booking_id: String(item.order_booking_id),
        source_type: "order",
        description: item.description,
        unit: item.unit,
        ordered_quantity: item.ordered_quantity,
        delivery_quantity: item.delivery_quantity,
        unit_price: item.unit_price,
        remarks: "",
        order_serial: "",
      });
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/delivery-challans")}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Delivery Challans
        </Button>
      </div>

      <div className="grid gap-6">
        <Card className="overflow-visible">
          <CardHeader>
            <CardTitle>Delivery Challan Details</CardTitle>
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
                <Label htmlFor="delivery_date">Delivery Date *</Label>
                <input
                  id="delivery_date"
                  type="date"
                  {...register("delivery_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.delivery_date && (
                  <p className="text-sm text-destructive">{errors.delivery_date.message}</p>
                )}
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="delivery_address">Delivery Address *</Label>
              <Textarea
                id="delivery_address"
                rows={2}
                {...register("delivery_address")}
                placeholder="Enter delivery address"
              />
              {errors.delivery_address && (
                <p className="text-sm text-destructive">{errors.delivery_address.message}</p>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Transport Information</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="transport_name">Transport Name</Label>
              <Input
                id="transport_name"
                placeholder="Enter transport name"
                {...register("transport_name")}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="vehicle_number">Vehicle Number</Label>
              <Input
                id="vehicle_number"
                placeholder="Enter vehicle number"
                {...register("vehicle_number")}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="driver_name">Driver Name</Label>
              <Input
                id="driver_name"
                placeholder="Enter driver name"
                {...register("driver_name")}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="driver_mobile">Driver Mobile</Label>
              <Input
                id="driver_mobile"
                placeholder="Enter driver mobile number"
                {...register("driver_mobile")}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="lr_number">LR / Consignment Number</Label>
              <Input
                id="lr_number"
                placeholder="Enter LR / consignment number"
                {...register("lr_number")}
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Status</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="status">Delivery Challan Status</Label>
              <Select
                value={currentStatus}
                onValueChange={(v) => v && setValue("status", v)}
                items={[
                  { value: "draft", label: "Draft" },
                  { value: "ready", label: "Ready" },
                  { value: "dispatched", label: "Dispatched" },
                  { value: "delivered", label: "Delivered" },
                  { value: "cancelled", label: "Cancelled" },
                ]}
              >
                <SelectTrigger id="status" className="w-full">
                  <SelectValue placeholder="Select status...">
                    {currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}
                  </SelectValue>
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="draft">Draft</SelectItem>
                  <SelectItem value="ready">Ready</SelectItem>
                  <SelectItem value="dispatched">Dispatched</SelectItem>
                  <SelectItem value="delivered">Delivered</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {currentStatus === "delivered" && (
              <div className="space-y-4 rounded-lg border p-4">
                <p className="text-sm font-medium text-muted-foreground">Delivery Information</p>
                <div className="space-y-2">
                  <Label htmlFor="delivery_by">Delivered By</Label>
                  <Input
                    id="delivery_by"
                    placeholder="Enter person who delivered"
                    {...register("delivery_by")}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="receiver_name">Received By</Label>
                  <Input
                    id="receiver_name"
                    placeholder="Enter receiver name"
                    {...register("receiver_name")}
                  />
                </div>
              </div>
            )}
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
                Import From Order
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {fields.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                No items yet. Import from an Order to get started.
              </p>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead className="w-16">Source</TableHead>
                      <TableHead className="min-w-36">Description</TableHead>
                      <TableHead className="w-14">Unit</TableHead>
                      <TableHead className="w-20">Delivery Qty</TableHead>
                      <TableHead className="min-w-24">Remarks</TableHead>
                      <TableHead className="w-20" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {fields.map((field, index) => (
                      <TableRow key={field.id}>
                        <TableCell>
                          <input type="hidden" {...register(`items.${index}.order_booking_item_id`)} />
                          <input type="hidden" {...register(`items.${index}.order_booking_id`)} />
                          <input type="hidden" {...register(`items.${index}.source_type`)} />
                          <input type="hidden" {...register(`items.${index}.ordered_quantity`)} />
                          <input type="hidden" {...register(`items.${index}.unit_price`)} />
                          <input type="hidden" {...register(`items.${index}.order_serial`)} />
                          <span className="text-xs font-medium uppercase text-muted-foreground">
                            Order
                          </span>
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
                        <TableCell>
                          <Input
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="0"
                            {...register(`items.${index}.delivery_quantity`)}
                          />
                          {errors.items?.[index]?.delivery_quantity && (
                            <p className="text-xs text-destructive">
                              {errors.items[index]?.delivery_quantity?.message}
                            </p>
                          )}
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
                    ))}
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
                  <span className="text-muted-foreground">Total Items</span>
                  <span className="font-medium">{totals.itemCount}</span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">Total Quantity</span>
                  <span className="font-medium">{totals.totalQuantity}</span>
                </div>
              </div>
            )}
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
            onClick={() => router.push("/delivery-challans")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Delivery Challan" : "Create Delivery Challan"}
          </Button>
        </div>
      </div>

      <ImportOrderDialog
        open={importDialogOpen}
        onOpenChange={setImportDialogOpen}
        customerId={selectedCustomer?.id ?? null}
        onImport={handleImportItems}
      />
    </form>
  );
}
