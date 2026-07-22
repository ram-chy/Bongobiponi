import { EditPublisherPage } from "@/features/publishers/components/edit-publisher-page";

export default async function EditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <EditPublisherPage id={Number(id)} />;
}
