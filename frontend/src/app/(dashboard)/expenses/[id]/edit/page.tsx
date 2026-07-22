import { EditExpensePage } from "@/features/expenses/components/edit-expense-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <EditExpensePage id={Number(id)} />;
}
