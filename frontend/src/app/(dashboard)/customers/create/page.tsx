import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { CustomerForm } from "@/features/customers/components/customer-form";

export default function CreateCustomerPage() {
  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Registration" },
          { label: "Customers", href: "/customers" },
          { label: "Create Customer" },
        ]}
      />
      <PageHeader title="Create Customer" />
      <CustomerForm />
    </div>
  );
}
