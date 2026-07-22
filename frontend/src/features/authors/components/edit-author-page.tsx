"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { AuthorForm } from "@/features/authors/components/author-form";
import { useAuthor } from "@/features/authors/hooks/use-author";
import { Loader2 } from "lucide-react";

export function EditAuthorPage({ id }: { id: number }) {
  const { data: author, isLoading } = useAuthor(id);

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
          { label: "Authors", href: "/authors" },
          { label: "Edit Author" },
        ]}
      />
      <PageHeader title="Edit Author" />
      <AuthorForm
        id={id}
        defaultValues={
          author
            ? {
                name: author.name,
                biography: author.biography ?? "",
                country: author.country ?? "",
                remarks: author.remarks ?? "",
                status: author.status,
              }
            : undefined
        }
      />
    </div>
  );
}
