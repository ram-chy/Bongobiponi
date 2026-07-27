"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";
import { authService } from "@/services/auth-service";
import { Loader2 } from "lucide-react";

const COOKIE_NAME = "auth_token";

function hasAuthCookie(): boolean {
  if (typeof document === "undefined") return false;
  return document.cookie
    .split(";")
    .some((c) => c.trim().startsWith(`${COOKIE_NAME}=`));
}

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [hydrating, setHydrating] = useState(true);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const setToken = useAuthStore((state) => state.setToken);
  const setUser = useAuthStore((state) => state.setUser);

  useEffect(() => {
    if (isAuthenticated) {
      setHydrating(false);
      return;
    }

    if (!hasAuthCookie()) {
      setHydrating(false);
      return;
    }

    let cancelled = false;

    const tokenMatch = document.cookie
      .split(";")
      .find((c) => c.trim().startsWith(`${COOKIE_NAME}=`));

    if (!tokenMatch) {
      setHydrating(false);
      return;
    }

    const token = decodeURIComponent(tokenMatch.split("=").slice(1).join("="));
    setToken(token);

    authService
      .me()
      .then((res) => {
        if (!cancelled) {
          setUser(res.data.data);
          setHydrating(false);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setToken(null);
          setUser(null);
          setHydrating(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    if (!hydrating && !isAuthenticated) {
      router.replace("/login");
    }
  }, [hydrating, isAuthenticated, router]);

  if (hydrating) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Loader2 className="size-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  return <>{children}</>;
}
