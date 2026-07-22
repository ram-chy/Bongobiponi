import { AuthGuard } from "@/components/common/auth-guard";
import { DashboardLayout } from "@/components/layout/dashboard-layout";

export default function DashboardRootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <AuthGuard>
      <DashboardLayout>{children}</DashboardLayout>
    </AuthGuard>
  );
}
