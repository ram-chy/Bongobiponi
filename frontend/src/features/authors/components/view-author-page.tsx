"use client";

import Link from "next/link";
import { ArrowLeft, Pencil } from "lucide-react";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Badge } from "@/components/ui/badge";
import { useAuthor } from "@/features/authors/hooks/use-author";
import { cn } from "@/lib/utils";

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function fmtDate(d: string | undefined | null): string {
  if (!d) return "-";
  const parts = d.split("T")[0].split("-");
  if (parts.length !== 3) return d.slice(0, 10);
  return `${parseInt(parts[2])} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
}

export function ViewAuthorPage({ id }: { id: number }) {
  const { data: author, isLoading } = useAuthor(id);

  if (isLoading) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Loading...</div>;
  }

  if (!author) {
    return <div className="flex items-center justify-center h-32 text-muted-foreground">Author not found.</div>;
  }

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Authors", href: "/authors" },
          { label: author.name },
        ]}
      />
      <PageHeader
        title={author.name}
        description="Author details"
        actions={
          <div className="flex items-center gap-2">
            <Link
              href={`/authors/${id}/edit`}
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
            <CardTitle>Author Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-sm text-muted-foreground">Name</p>
                <p className="font-medium">{author.name}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Country</p>
                <p className="font-medium">{author.country || "-"}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge
                  variant={author.status ? "default" : "destructive"}
                  className={author.status ? "bg-emerald-600 hover:bg-emerald-600/80" : undefined}
                >
                  {author.status ? "ACTIVE" : "INACTIVE"}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        {author.biography && (
          <Card>
            <CardHeader>
              <CardTitle>Biography</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm whitespace-pre-wrap">{author.biography}</p>
            </CardContent>
          </Card>
        )}

        {author.remarks && (
          <Card>
            <CardHeader>
              <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm">{author.remarks}</p>
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
                  {author.created_by
                    ? `${author.created_by.first_name} ${author.created_by.last_name}`
                    : "-"}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Created At</p>
                <p className="font-medium">{fmtDate(author.created_at)}</p>
              </div>
              {author.updated_by && (
                <>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated By</p>
                    <p className="font-medium">
                      {author.updated_by.first_name} {author.updated_by.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Updated At</p>
                    <p className="font-medium">{fmtDate(author.updated_at)}</p>
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
