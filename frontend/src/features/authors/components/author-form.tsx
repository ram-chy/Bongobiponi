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
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Loader2, ArrowLeft } from "lucide-react";
import { useAuthorForm } from "@/features/authors/hooks/use-author-form";
import { mapValidationErrors } from "@/lib/api-errors";

const authorSchema = z.object({
  name: z.string().min(1, "Name is required").max(255),
  biography: z.string().optional().or(z.literal("")),
  country: z.string().max(255).optional().or(z.literal("")),
  remarks: z.string().optional().or(z.literal("")),
  status: z.boolean(),
});

type AuthorFormData = z.infer<typeof authorSchema>;

interface AuthorFormProps {
  defaultValues?: Partial<AuthorFormData>;
  id?: number;
}

export function AuthorForm({ defaultValues, id }: AuthorFormProps) {
  const router = useRouter();
  const authorMutation = useAuthorForm({ id });

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<AuthorFormData>({
    resolver: zodResolver(authorSchema),
    defaultValues: {
      status: true,
      ...defaultValues,
    },
  });

  const onSubmit = async (data: AuthorFormData) => {
    try {
      await authorMutation.mutateAsync(data as unknown as Record<string, unknown>);
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-6">
        <Button
          type="button"
          variant="ghost"
          onClick={() => router.push("/authors")}
        >
          <ArrowLeft className="size-4" />
          Back to Authors
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Author Information</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="name">Name *</Label>
              <Input id="name" {...register("name")} />
              {errors.name && (
                <p className="text-sm text-destructive">{errors.name.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="country">Country</Label>
              <Input id="country" {...register("country")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <Select
                defaultValue={defaultValues?.status === false ? "inactive" : "active"}
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
              <Label htmlFor="biography">Biography</Label>
              <Textarea id="biography" rows={4} {...register("biography")} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="remarks">Remarks</Label>
              <Textarea id="remarks" {...register("remarks")} />
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/authors")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Author" : "Create Author"}
          </Button>
        </div>
      </div>
    </form>
  );
}
