import { GuestGuard } from "@/components/common/guest-guard";
import Image from "next/image";

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <GuestGuard>
      <div className="flex min-h-screen">
        <div className="hidden w-1/2 items-center justify-center bg-background lg:flex">
          <div className="rounded-2xl p-4 shadow-[0_0_30px_4px_rgba(34,197,94,0.5)]">
            <Image
              src="/logo.png"
              alt="Bongobiponi"
              width={200}
              height={200}
              className="rounded-2xl"
            />
          </div>
        </div>
        <div className="flex w-full items-center justify-center bg-background p-4 lg:w-1/2">
          {children}
        </div>
      </div>
    </GuestGuard>
  );
}
