"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { CategoryForm } from "@/features/categories/components/category-form";

export function CreateCategoryPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Categories", href: "/categories" },
          { label: "Create Category" },
        ]}
      />
      <PageHeader title="Create Category" />
      <CategoryForm />
    </div>
  );
}
