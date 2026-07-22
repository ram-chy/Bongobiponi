"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2, ArrowLeft } from "lucide-react";
import { useCategoryForm } from "@/features/categories/hooks/use-category-form";
import { mapValidationErrors } from "@/lib/api-errors";
import { useQuery } from "@tanstack/react-query";
import { categoryService } from "@/services/category-service";
import type { Category } from "@/types/category";

const categorySchema = z.object({
  parent_id: z.string().optional().or(z.literal("")),
  name: z.string().min(1, "Name is required").max(255),
  description: z.string().optional().or(z.literal("")),
  status: z.boolean(),
});

type CategoryFormData = z.infer<typeof categorySchema>;

interface CategoryFormProps {
  defaultValues?: Partial<CategoryFormData>;
  id?: number;
}

export function CategoryForm({ defaultValues, id }: CategoryFormProps) {
  const router = useRouter();
  const categoryMutation = useCategoryForm({ id });

  const { data: parentCategories } = useQuery({
    queryKey: ["/categories", "all"],
    queryFn: async () => {
      const response = await categoryService.listAll();
      return response.data.data;
    },
  });

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    watch,
    formState: { errors, isSubmitting },
  } = useForm<CategoryFormData>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      status: true,
      ...defaultValues,
    },
  });

  const selectedParentId = watch("parent_id");

  const onSubmit = async (data: CategoryFormData) => {
    const payload = {
      ...data,
      parent_id: data.parent_id ? Number(data.parent_id) : null,
    };
    try {
      await categoryMutation.mutateAsync(
        payload as unknown as Record<string, unknown>
      );
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  const filteredParents = parentCategories?.filter(
    (cat: Category) => cat.id !== id
  );

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/categories")}
        >
          <ArrowLeft className="size-4" />
          Back to Categories
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Category Information</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="name">Name *</Label>
              <Input id="name" {...register("name")} />
              {errors.name && (
                <p className="text-sm text-destructive">
                  {errors.name.message}
                </p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="parent_id">Parent Category</Label>
              <Select
                value={selectedParentId || null}
                onValueChange={(value) =>
                  setValue("parent_id", value === "none" ? "" : value ?? "")
                }
                items={[
                  { value: "none", label: "None (Top Level)" },
                  ...(filteredParents?.map((cat: Category) => ({ value: String(cat.id), label: cat.name })) ?? []),
                ]}
              >
                <SelectTrigger>
                  <SelectValue placeholder="None (Top Level)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None (Top Level)</SelectItem>
                  {filteredParents?.map((cat: Category) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <Select
                defaultValue={
                  defaultValues?.status === false ? "inactive" : "active"
                }
                onValueChange={(value) =>
                  setValue("status", value === "active")
                }
                items={[
                  { value: "active", label: "Active" },
                  { value: "inactive", label: "Inactive" },
                ]}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Description</Label>
              <Textarea id="description" {...register("description")} />
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/categories")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Category" : "Create Category"}
          </Button>
        </div>
      </div>
    </form>
  );
}
