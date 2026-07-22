"use client";

import { useRouter } from "next/navigation";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Button } from "@/components/ui/button";
import { ArrowLeft, Loader2 } from "lucide-react";
import { PaymentForm } from "@/features/payments/components/payment-form";
import { usePayment } from "@/features/payments/hooks/use-payment";

interface EditPaymentPageProps {
  id: number;
}

export function EditPaymentPage({ id }: EditPaymentPageProps) {
  const router = useRouter();
  const { data: payment, isLoading } = usePayment(id);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!payment) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Payment not found.
      </div>
    );
  }

  const formDefaults = {
    customer_id: String(payment.customer!.id),
    payment_date: payment.payment_date ? payment.payment_date.split("T")[0] : "",
    payment_method: payment.payment_method,
    reference_no: payment.reference_no ?? "",
    remarks: payment.remarks ?? "",
    items: payment.items.map((item) => ({
      invoice_id: String(item.invoice?.id ?? item.id),
      paid_amount: item.paid_amount,
    })),
  };

  return (
    <div>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push(`/payments/${id}`)}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Payment
        </Button>
      </div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Payments", href: "/payments" },
          { label: payment.payment_no },
        ]}
      />
      <PageHeader title={`Edit ${payment.payment_no}`} />
      <PaymentForm
        defaultValues={formDefaults}
        id={id}
        customer={payment.customer}
      />
    </div>
  );
}
