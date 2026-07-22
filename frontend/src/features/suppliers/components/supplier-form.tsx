"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
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
import { Loader2, ArrowLeft } from "lucide-react";
import { useSupplierForm } from "@/features/suppliers/hooks/use-supplier-form";
import { mapValidationErrors } from "@/lib/api-errors";

const supplierSchema = z.object({
  name: z.string().min(1, "Name is required").max(255),
  company_name: z.string().min(1, "Company name is required").max(255),
  phone: z.string().min(1, "Phone is required").max(20),
  email: z.string().email().optional().or(z.literal("")),
  gst_number: z.string().max(20).optional().or(z.literal("")),
  address: z.string().min(1, "Address is required"),
  remarks: z.string().optional().or(z.literal("")),
  status: z.boolean(),
});

type SupplierFormData = z.infer<typeof supplierSchema>;

interface SupplierFormProps {
  defaultValues?: Partial<SupplierFormData>;
  id?: number;
}

export function SupplierForm({ defaultValues, id }: SupplierFormProps) {
  const router = useRouter();
  const supplierMutation = useSupplierForm({ id });

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<SupplierFormData>({
    resolver: zodResolver(supplierSchema),
    defaultValues: {
      status: true,
      ...defaultValues,
    },
  });

  const onSubmit = async (data: SupplierFormData) => {
    try {
      await supplierMutation.mutateAsync(data as unknown as Record<string, unknown>);
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
          onClick={() => router.push("/suppliers")}
        >
          <ArrowLeft className="size-4" />
          Back to Suppliers
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="name">Name *</Label>
              <Input id="name" {...register("name")} />
              {errors.name && (
                <p className="text-sm text-destructive">{errors.name.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="company_name">Company Name *</Label>
              <Input id="company_name" {...register("company_name")} />
              {errors.company_name && (
                <p className="text-sm text-destructive">{errors.company_name.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Phone *</Label>
              <Input id="phone" {...register("phone")} />
              {errors.phone && (
                <p className="text-sm text-destructive">{errors.phone.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" {...register("email")} />
              {errors.email && (
                <p className="text-sm text-destructive">{errors.email.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="gst_number">GST Number</Label>
              <Input id="gst_number" {...register("gst_number")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <Select
                defaultValue={defaultValues?.status === false ? "inactive" : "active"}
                onValueChange={(value) =>
                  setValue("status", value === "active")
                }
                items={[
                  { value: "active", label: "Active" },
                  { value: "inactive", label: "Inactive" },
                ]}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="address">Address *</Label>
              <Textarea id="address" {...register("address")} />
              {errors.address && (
                <p className="text-sm text-destructive">{errors.address.message}</p>
              )}
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="remarks">Remarks</Label>
              <Textarea id="remarks" {...register("remarks")} />
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/suppliers")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Supplier" : "Create Supplier"}
          </Button>
        </div>
      </div>
    </form>
  );
}
