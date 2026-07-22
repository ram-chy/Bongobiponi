import { ViewPaymentPage } from "@/features/payments/components/view-payment-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewPaymentPage id={Number(id)} />;
}
