"use client";

import Link from "next/link";
import { ArrowLeft, Pencil } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { usePublisher } from "@/features/publishers/hooks/use-publisher";
import { cn } from "@/lib/utils";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

export function ViewPublisherPage({ id }: { id: number }) {
  const { data: publisher, isLoading } = usePublisher(id);

  if (isLoading) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Loading...</div>;
  }

  if (!publisher) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Publisher not found.</div>;
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Publishers", href: "/publishers" },
          { label: publisher.name },
        ]}
      />
      <PageHeader
        title={publisher.name}
        description="Publisher details"
        actions={
          <div className="flex items-center gap-2">
            <Link
              href={`/publishers/${id}/edit`}
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
            <CardTitle>Publisher Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Name</p>
                <p className="font-medium">{publisher.name}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Phone</p>
                <p className="font-medium">{publisher.phone || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Email</p>
                <p className="font-medium">{publisher.email || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={publisher.status ? "default" : "destructive"}
                  className={publisher.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
                >
                  {publisher.status ? "ACTIVE" : "INACTIVE"}
                </Badge>
              </div>
              <div className="sm:col-span-2">
                <p className="text-sm text-muted-foreground">Address</p>
                <p className="font-medium">{publisher.address}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {publisher.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm">{publisher.remarks}</p>
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
                  {publisher.created_by
                    ? `${publisher.created_by.first_name} ${publisher.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">{fmtDate(publisher.created_at)}</p>
              </div>
              {publisher.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {publisher.updated_by.first_name} {publisher.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">{fmtDate(publisher.updated_at)}</p>
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
