"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { SupplierForm } from "@/features/suppliers/components/supplier-form";
import { useSupplier } from "@/features/suppliers/hooks/use-supplier";
import { Loader2 } from "lucide-react";

export function EditSupplierPage({ id }: { id: number }) {
  const { data: supplier, isLoading } = useSupplier(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Suppliers", href: "/suppliers" },
          { label: "Edit Supplier" },
        ]}
      />
      <PageHeader title="Edit Supplier" />
      <SupplierForm
        id={id}
        defaultValues={
          supplier
            ? {
                name: supplier.name,
                company_name: supplier.company_name,
                phone: supplier.phone,
                email: supplier.email ?? "",
                gst_number: supplier.gst_number ?? "",
                address: supplier.address,
                remarks: supplier.remarks ?? "",
                status: supplier.status,
              }
            : undefined
        }
      />
    </div>
  );
}
