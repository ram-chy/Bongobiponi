import Link from "next/link";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export default function ForbiddenPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-4">
      <h1 className="text-6xl font-bold text-muted-foreground">403</h1>
      <h2 className="text-xl font-semibold">Access Denied</h2>
      <p className="text-sm text-muted-foreground text-center max-w-md">
        You do not have permission to access this resource.
      </p>
      <Link
        href="/dashboard"
        className={cn(buttonVariants({ variant: "default" }))}
      >
        Go to Dashboard
      </Link>
    </div>
  );
}
