"use client";

import { useAuthStore } from "@/store/auth-store";
import { can, type CrudAction } from "@/lib/permissions";

export function usePermissions() {
  const user = useAuthStore((state) => state.user);

  return {
    can: (action: CrudAction) => can(user, action),
    user,
  };
}
