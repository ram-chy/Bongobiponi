"use client";

import Link from "next/link";
import { ArrowLeft, Pencil } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { useSupplier } from "@/features/suppliers/hooks/use-supplier";
import { cn } from "@/lib/utils";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

export function ViewSupplierPage({ id }: { id: number }) {
  const { data: supplier, isLoading } = useSupplier(id);

  if (isLoading) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Loading...</div>;
  }

  if (!supplier) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Supplier not found.</div>;
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Suppliers", href: "/suppliers" },
          { label: supplier.name },
        ]}
      />
      <PageHeader
        title={supplier.name}
        description="Supplier details"
        actions={
          <div className="flex items-center gap-2">
            <Link
              href={`/suppliers/${id}/edit`}
              className={cn(buttonVariants({ variant: "outline" }), "gap-1.5")}
            >
              <Pencil className="size-4" />
              Edit
            </Link>
          </div>
        }
      />

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Supplier Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Name</p>
                <p className="font-medium">{supplier.name}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Company Name</p>
                <p className="font-medium">{supplier.company_name}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Phone</p>
                <p className="font-medium">{supplier.phone}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Email</p>
                <p className="font-medium">{supplier.email || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">GST Number</p>
                <p className="font-medium">{supplier.gst_number || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={supplier.status ? "default" : "destructive"}
                  className={supplier.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
                >
                  {supplier.status ? "ACTIVE" : "INACTIVE"}
                </Badge>
              </div>
              <div className="sm:col-span-2">
                <p className="text-sm text-muted-foreground">Address</p>
                <p className="font-medium">{supplier.address}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {supplier.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm">{supplier.remarks}</p>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>System Info</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Created By</p>
                <p className="font-medium">
                  {supplier.created_by
                    ? `${supplier.created_by.first_name} ${supplier.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">{fmtDate(supplier.created_at)}</p>
              </div>
              {supplier.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {supplier.updated_by.first_name} {supplier.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">{fmtDate(supplier.updated_at)}</p>
                  </div>
                </>
              )}
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button type="button" variant="outline" onClick={() => window.history.back()}>
            <ArrowLeft className="size-4" />
            Back
          </Button>
        </div>
      </div>
    </div>
  );
}
