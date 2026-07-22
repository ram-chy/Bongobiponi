import type { User } from "@/types/auth";

export type CrudAction = "view" | "create" | "update" | "delete";

export function can(user: User | null, action: CrudAction): boolean {
  if (!user?.role?.slug) return false;

  switch (action) {
    case "view":
    case "create":
      return true;
    case "update":
      return user.role.slug === "admin" || user.role.slug === "manager";
    case "delete":
      return user.role.slug === "admin";
  }
}
