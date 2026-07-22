import { ViewExpensePage } from "@/features/expenses/components/view-expense-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewExpensePage id={Number(id)} />;
}
