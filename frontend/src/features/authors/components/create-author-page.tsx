"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { AuthorForm } from "@/features/authors/components/author-form";

export function CreateAuthorPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Authors", href: "/authors" },
          { label: "Create Author" },
        ]}
      />
      <PageHeader title="Create Author" />
      <AuthorForm />
    </div>
  );
}
