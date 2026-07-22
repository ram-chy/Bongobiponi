"use client";

import { useState, useEffect } from "react";
import { Search, Shield } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { ChangeRoleDialog } from "@/features/user-management/components/change-role-dialog";
import { useUsers } from "@/features/user-management/hooks/use-users";
import { ROLES } from "@/features/user-management/constants";
import type { UserData } from "@/types/user";

export function UsersPage() {
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [page, setPage] = useState(0);
  const [selectedUser, setSelectedUser] = useState<UserData | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(0);
    }, 400);
    return () => clearTimeout(timeout);
  }, [search]);

  const { data, isLoading } = useUsers({
    search: debouncedSearch || undefined,
    page: page + 1,
    per_page: 10,
  });

  const users = data?.data ?? [];
  const meta = data?.meta;
  const totalPages = meta?.last_page ?? 1;

  const getRoleBadgeVariant = (slug: string | undefined) => {
    switch (slug) {
      case "admin":
        return "default" as const;
      case "manager":
        return "secondary" as const;
      default:
        return "outline" as const;
    }
  };

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Registration" },
          { label: "User Management" },
        ]}
      />
      <PageHeader
        title="User Management"
        description="Manage system users and their roles."
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search users..."
            className="pl-8"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
      </div>

      <div className="rounded-lg border overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Mobile</TableHead>
              <TableHead>Role</TableHead>
              <TableHead className="w-20">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={5} className="h-32 text-center">
                  Loading...
                </TableCell>
              </TableRow>
            ) : users.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={5}
                  className="h-32 text-center text-muted-foreground"
                >
                  No users found.
                </TableCell>
              </TableRow>
            ) : (
              users.map((user) => (
                <TableRow key={user.id}>
                  <TableCell>
                    <div>
                      <p className="font-medium">
                        {user.first_name} {user.last_name}
                      </p>
                    </div>
                  </TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>{user.mobile_no}</TableCell>
                  <TableCell>
                    <Badge
                      variant={getRoleBadgeVariant(user.role?.slug)}
                      className={
                        user.role?.slug === "admin"
                          ? "bg-primary hover:bg-primary/80"
                          : user.role?.slug === "manager"
                            ? "bg-emerald-600 hover:bg-emerald-600/80 text-white"
                            : undefined
                      }
                    >
                      {user.role?.name ?? "N/A"}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      onClick={() => {
                        setSelectedUser(user);
                        setDialogOpen(true);
                      }}
                      title="Change Role"
                    >
                      <Shield className="size-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {totalPages > 1 && (
        <div className="flex items-center justify-between gap-4 pt-4">
          <p className="text-sm text-muted-foreground">
            Page {page + 1} of {totalPages}
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page === 0}
              onClick={() => setPage(page - 1)}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= totalPages - 1}
              onClick={() => setPage(page + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      )}

      <ChangeRoleDialog
        user={selectedUser}
        roles={ROLES}
        open={dialogOpen}
        onOpenChange={setDialogOpen}
      />
    </div>
  );
}
