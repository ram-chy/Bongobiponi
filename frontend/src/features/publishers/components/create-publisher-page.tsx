"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { PublisherForm } from "@/features/publishers/components/publisher-form";

export function CreatePublisherPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Master Data" },
          { label: "Publishers", href: "/publishers" },
          { label: "Create Publisher" },
        ]}
      />
      <PageHeader title="Create Publisher" />
      <PublisherForm />
    </div>
  );
}
