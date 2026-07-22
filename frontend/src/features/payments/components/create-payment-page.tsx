import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { PaymentForm } from "@/features/payments/components/payment-form";

export function CreatePaymentPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Sales" },
          { label: "Payments", href: "/payments" },
          { label: "Create Payment" },
        ]}
      />
      <PageHeader title="Create Payment" />
      <PaymentForm />
    </div>
  );
}
