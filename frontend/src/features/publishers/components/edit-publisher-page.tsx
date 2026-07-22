"use client";

import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { PublisherForm } from "@/features/publishers/components/publisher-form";
import { usePublisher } from "@/features/publishers/hooks/use-publisher";
import { Loader2 } from "lucide-react";

export function EditPublisherPage({ id }: { id: number }) {
  const { data: publisher, isLoading } = usePublisher(id);

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
          { label: "Publishers", href: "/publishers" },
          { label: "Edit Publisher" },
        ]}
      />
      <PageHeader title="Edit Publisher" />
      <PublisherForm
        id={id}
        defaultValues={
          publisher
            ? {
                name: publisher.name,
                phone: publisher.phone ?? "",
                email: publisher.email ?? "",
                address: publisher.address,
                remarks: publisher.remarks ?? "",
                status: publisher.status,
              }
            : undefined
        }
      />
    </div>
  );
}
