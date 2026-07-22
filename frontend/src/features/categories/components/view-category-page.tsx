"use client";

import Link from "next/link";
import { ArrowLeft, Pencil } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { useCategory } from "@/features/categories/hooks/use-category";
import { cn } from "@/lib/utils";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

export function ViewCategoryPage({ id }: { id: number }) {
  const { data: category, isLoading } = useCategory(id);

  if (isLoading) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Loading...</div>;
  }

  if (!category) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Category not found.</div>;
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Categories", href: "/categories" },
          { label: category.name },
        ]}
      />
      <PageHeader
        title={category.name}
        description="Category details"
        actions={
          <div className="flex items-center gap-2">
            <Link
              href={`/categories/${id}/edit`}
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
            <CardTitle>Category Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Name</p>
                <p className="font-medium">{category.name}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Parent Category</p>
                <p className="font-medium">{category.parent?.name || "None (Top Level)"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={category.status ? "default" : "destructive"}
                  className={category.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
                >
                  {category.status ? "ACTIVE" : "INACTIVE"}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        {category.description && (
          <Card>
            <CardHeader>
              <CardTitle>Description</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm">{category.description}</p>
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
                  {category.created_by
                    ? `${category.created_by.first_name} ${category.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">{fmtDate(category.created_at)}</p>
              </div>
              {category.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {category.updated_by.first_name} {category.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">{fmtDate(category.updated_at)}</p>
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
