"use client";

import { useAuthStore } from "@/store/auth-store";
import { useRouter } from "next/navigation";
import { useEffect } from "react";

interface RoleGuardProps {
  children: React.ReactNode;
  allowedRoles: string[];
  fallback?: React.ReactNode;
}

export function RoleGuard({
  children,
  allowedRoles,
  fallback,
}: RoleGuardProps) {
  const router = useRouter();
  const user = useAuthStore((state) => state.user);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  useEffect(() => {
    if (isAuthenticated && user && !allowedRoles.includes(user.role?.slug ?? "")) {
      router.replace("/forbidden");
    }
  }, [isAuthenticated, user, allowedRoles, router]);

  if (!isAuthenticated || !user) return null;

  if (!allowedRoles.includes(user.role?.slug ?? "")) {
    return fallback ?? null;
  }

  return <>{children}</>;
}
