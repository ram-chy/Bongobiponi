import { GuestGuard } from "@/components/common/guest-guard";

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <GuestGuard>
      <div className="flex min-h-screen">
        <div
          className="hidden w-1/2 bg-cover bg-center bg-no-repeat lg:block"
          style={{ backgroundImage: "url('/logo.png')" }}
        />
        <div className="flex w-full items-center justify-center bg-background p-4 lg:w-1/2">
          {children}
        </div>
      </div>
    </GuestGuard>
  );
}
