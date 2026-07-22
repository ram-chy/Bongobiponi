"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { CategoryForm } from "@/features/categories/components/category-form";
import { useCategory } from "@/features/categories/hooks/use-category";
import { Loader2 } from "lucide-react";

export function EditCategoryPage({ id }: { id: number }) {
  const { data: category, isLoading } = useCategory(id);

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
          { label: "Categories", href: "/categories" },
          { label: "Edit Category" },
        ]}
      />
      <PageHeader title="Edit Category" />
      <CategoryForm
        id={id}
        defaultValues={
          category
            ? {
                parent_id: category.parent_id
                  ? String(category.parent_id)
                  : "",
                name: category.name,
                description: category.description ?? "",
                status: category.status,
              }
            : undefined
        }
      />
    </div>
  );
}
