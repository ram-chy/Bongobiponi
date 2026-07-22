"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { CustomerForm } from "@/features/customers/components/customer-form";
import { useCustomer } from "@/features/customers/hooks/use-customer";
import { Loader2 } from "lucide-react";

export function CustomerEditPage({ id }: { id: number }) {
  const { data: customer, isLoading } = useCustomer(id);

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
          { label: "Registration" },
          { label: "Customers", href: "/customers" },
          { label: "Edit Customer" },
        ]}
      />
      <PageHeader title="Edit Customer" />
      <CustomerForm
        id={id}
        defaultValues={
          customer
            ? {
                name: customer.name,
                company_name: customer.company_name ?? "",
                email: customer.email ?? "",
                phone: customer.phone,
                alternate_phone: customer.alternate_phone ?? "",
                gst_number: customer.gst_number ?? "",
                pan_number: customer.pan_number ?? "",
                billing_address: customer.billing_address,
                shipping_address: customer.shipping_address ?? "",
                city: customer.city,
                state: customer.state,
                country: customer.country,
                postal_code: customer.postal_code,
                credit_limit: customer.credit_limit,
                opening_balance: customer.opening_balance,
                status: customer.status,
                notes: customer.notes ?? "",
              }
            : undefined
        }
      />
    </div>
  );
}
