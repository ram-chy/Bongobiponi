import { ViewPublisherPage } from "@/features/publishers/components/view-publisher-page";

export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ViewPublisherPage id={Number(id)} />;
}
