import type { UserRole } from "@/types/user";

export const ROLES: UserRole[] = [
  { id: 1, name: "Admin", slug: "admin" },
  { id: 2, name: "Manager", slug: "manager" },
  { id: 3, name: "Regular User", slug: "regular_user" },
];
