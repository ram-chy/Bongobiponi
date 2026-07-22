"use client";

import { useState, useEffect } from "react";
import { Search, Plus, Pencil, Trash2 } from "lucide-react";
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
import { ConfirmDialog } from "@/components/dialogs/confirm-dialog";
import { ExpenseCategoryDialog } from "@/features/expenses/components/expense-category-dialog";
import { useExpenseCategoryList } from "@/features/expenses/hooks/use-expense-category-list";
import { expenseCategoryService } from "@/services/expense-service";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { buttonVariants } from "@/components/ui/button";
import type { ExpenseCategory } from "@/types/expense";

export function ExpenseCategoriesPage() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [page, setPage] = useState(0);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<ExpenseCategory | null>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedSearch(search);
      setPage(0);
    }, 400);
    return () => clearTimeout(timeout);
  }, [search]);

  const { data, isLoading } = useExpenseCategoryList({
    search: debouncedSearch || undefined,
    page: page + 1,
    per_page: 10,
  });

  const categories: ExpenseCategory[] = data?.data ?? [];
  const meta = data?.meta;
  const totalPages = meta?.last_page ?? 1;

  const handleDelete = async () => {
    if (!deleteId) return;
    try {
      await expenseCategoryService.delete(deleteId);
      queryClient.invalidateQueries({ queryKey: ["/expense-categories"] });
      toast.success("Category deleted successfully");
      setDeleteId(null);
    } catch {
      toast.error("Failed to delete category");
    }
  };

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Accounts" },
          { label: "Expense Categories" },
        ]}
      />
      <PageHeader
        title="Expense Categories"
        description="Manage expense categories."
        actions={
          <Button
            onClick={() => {
              setEditingCategory(null);
              setDialogOpen(true);
            }}
            className="gap-1.5"
          >
            <Plus className="size-4" />
            New Category
          </Button>
        }
      />

      <div className="mb-4 flex items-center gap-2">
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search categories..."
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
              <TableHead>Description</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={4} className="h-32 text-center">
                  Loading...
                </TableCell>
              </TableRow>
            ) : categories.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={4}
                  className="h-32 text-center text-muted-foreground"
                >
                  No categories found.
                </TableCell>
              </TableRow>
            ) : (
              categories.map((category) => (
                <TableRow key={category.id}>
                  <TableCell className="font-medium">{category.name}</TableCell>
                  <TableCell className="text-muted-foreground">
                    {category.description ?? "-"}
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant={category.is_active ? "default" : "outline"}
                      className={
                        category.is_active
                          ? "bg-emerald-600 hover:bg-emerald-600/80"
                          : ""
                      }
                    >
                      {category.is_active ? "Active" : "Inactive"}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        onClick={() => {
                          setEditingCategory(category);
                          setDialogOpen(true);
                        }}
                        title="Edit"
                      >
                        <Pencil className="size-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        onClick={() => setDeleteId(category.id)}
                        title="Delete"
                      >
                        <Trash2 className="size-4 text-destructive" />
                      </Button>
                    </div>
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

      <ExpenseCategoryDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        category={editingCategory}
      />

      <ConfirmDialog
        open={deleteId !== null}
        onOpenChange={(open) => {
          if (!open) setDeleteId(null);
        }}
        title="Delete Category?"
        description={
          deleteId
            ? `Category: ${categories.find((c) => c.id === deleteId)?.name ?? deleteId}`
            : ""
        }
        confirmLabel="Delete"
        onConfirm={handleDelete}
        variant="destructive"
      />
    </div>
  );
}
