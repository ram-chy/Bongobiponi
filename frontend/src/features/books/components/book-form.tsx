"use client";

import { useEffect, useRef, useState } from "react";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
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
import { Loader2, ArrowLeft, Search, Upload, X } from "lucide-react";
import { useBookForm } from "@/features/books/hooks/use-book-form";
import { mapValidationErrors } from "@/lib/api-errors";
import { publisherService } from "@/services/publisher-service";
import { categoryService } from "@/services/category-service";
import { authorService } from "@/services/author-service";
import { bookService } from "@/services/book-service";
import type { Publisher } from "@/types/publisher";
import type { Category } from "@/types/category";
import type { Author } from "@/types/author";

const bookSchema = z.object({
  isbn: z.string().max(20).optional().or(z.literal("")),
  barcode: z.string().max(20).optional().or(z.literal("")),
  title: z.string().min(1, "Title is required").max(500),
  subtitle: z.string().max(500).optional().or(z.literal("")),
  publisher_id: z.string().optional().or(z.literal("")),
  category_id: z.string().optional().or(z.literal("")),
  edition: z.string().max(50).optional().or(z.literal("")),
  language: z.string().max(50).optional().or(z.literal("")),
  purchase_price: z.string().min(1, "Purchase price is required"),
  selling_price: z.string().min(1, "Selling price is required"),
  minimum_stock: z.string().min(1, "Minimum stock is required"),
  authors: z.array(z.number()).min(1, "At least one author is required"),
  cover_image: z.string().optional().or(z.literal("")),
  description: z.string().optional().or(z.literal("")),
});

type BookFormData = z.infer<typeof bookSchema>;

interface BookFormProps {
  defaultValues?: Partial<BookFormData>;
  id?: number;
}

export function BookForm({ defaultValues, id }: BookFormProps) {
  const router = useRouter();
  const bookMutation = useBookForm({ id });
  const [authorSearch, setAuthorSearch] = useState("");
  const [coverPreview, setCoverPreview] = useState<string | null>(() => {
    if (defaultValues?.cover_image) {
      const storageUrl =
        (process.env.NEXT_PUBLIC_API_URL ?? "").replace(/\/api$/, "") +
        "/storage/";
      return storageUrl + defaultValues.cover_image;
    }
    return null;
  });
  const [isUploading, setIsUploading] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const initializedIdRef = useRef<number | undefined>(undefined);
  const coverUserInteracted = useRef(false);

  const { data: publishersData } = useQuery({
    queryKey: ["/publishers", "all"],
    queryFn: async () => {
      const response = await publisherService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const { data: categoriesData } = useQuery({
    queryKey: ["/categories", "all"],
    queryFn: async () => {
      const response = await categoryService.listAll();
      return response.data.data;
    },
  });

  const { data: authorsData } = useQuery({
    queryKey: ["/authors", "all"],
    queryFn: async () => {
      const response = await authorService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    watch,
    reset,
    control,
    formState: { errors, isSubmitting },
  } = useForm<BookFormData>({
    resolver: zodResolver(bookSchema),
    defaultValues: {
      authors: [],
      ...defaultValues,
    },
  });

  useEffect(() => {
    if (defaultValues && initializedIdRef.current !== id) {
      initializedIdRef.current = id;
      coverUserInteracted.current = false;
      reset({
        authors: [],
        ...defaultValues,
      });
      if (defaultValues.cover_image) {
        const storageUrl =
          (process.env.NEXT_PUBLIC_API_URL ?? "").replace(/\/api$/, "") +
          "/storage/";
        setCoverPreview(storageUrl + defaultValues.cover_image);
      } else {
        setCoverPreview(null);
      }
    }
  }, [defaultValues, reset, id]);

  const selectedPublisherId = watch("publisher_id");
  const selectedCategoryId = watch("category_id");
  const selectedAuthorIds = watch("authors");

  const filteredAuthors = authorsData?.filter((author: Author) =>
    author.name.toLowerCase().includes(authorSearch.toLowerCase())
  );

  const toggleAuthor = (authorId: number) => {
    const current = selectedAuthorIds || [];
    if (current.includes(authorId)) {
      setValue(
        "authors",
        current.filter((id) => id !== authorId),
        { shouldValidate: true }
      );
    } else {
      setValue("authors", [...current, authorId], {
        shouldValidate: true,
      });
    }
  };

  const handleCoverUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    coverUserInteracted.current = true;
    setUploadError(null);
    const previewUrl = URL.createObjectURL(file);
    setCoverPreview(previewUrl);

    setIsUploading(true);
    try {
      const response = await bookService.uploadCover(file);
      setValue("cover_image", response.data.data.path);
    } catch (err: unknown) {
      setCoverPreview(null);
      const axiosError = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> }; status?: number }; message?: string };
      const serverMsg = axiosError?.response?.data?.message;
      const serverErrors = axiosError?.response?.data?.errors;
      const detail = serverErrors
        ? Object.values(serverErrors).flat().join("; ")
        : serverMsg || axiosError?.message || "Upload failed. Please try again.";
      setUploadError(detail);
    } finally {
      setIsUploading(false);
    }

    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  };

  const handleRemoveCover = () => {
    setCoverPreview(null);
    setValue("cover_image", "");
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  };

  const onSubmit = async (data: BookFormData) => {
    const { authors, cover_image, ...rest } = data;
    const payload = {
      ...rest,
      isbn: rest.isbn || null,
      barcode: rest.barcode || null,
      subtitle: rest.subtitle || null,
      publisher_id: rest.publisher_id ? Number(rest.publisher_id) : null,
      category_id: rest.category_id ? Number(rest.category_id) : null,
      edition: rest.edition || null,
      language: rest.language || null,
      purchase_price: Number(rest.purchase_price),
      selling_price: Number(rest.selling_price),
      minimum_stock: Number(rest.minimum_stock),
      cover_image: cover_image || null,
      description: rest.description || null,
      authors: authors,
    };
    try {
      await bookMutation.mutateAsync(
        payload as unknown as Record<string, unknown>
      );
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
          onClick={() => router.push("/books")}
        >
          <ArrowLeft className="size-4" />
          Back to Books
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Book Information</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="title">Title *</Label>
              <Input id="title" {...register("title")} />
              {errors.title && (
                <p className="text-sm text-destructive">
                  {errors.title.message}
                </p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="subtitle">Subtitle</Label>
              <Input id="subtitle" {...register("subtitle")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="isbn">ISBN</Label>
              <Input id="isbn" {...register("isbn")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="barcode">Barcode</Label>
              <Input id="barcode" {...register("barcode")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="publisher_id">Publisher</Label>
              <Select
                value={selectedPublisherId || null}
                onValueChange={(value: string | null) =>
                  setValue("publisher_id", value === "none" ? "" : value ?? "")
                }
                items={[
                  { value: "none", label: "None" },
                  ...(publishersData?.map((pub: Publisher) => ({
                    value: String(pub.id),
                    label: pub.name,
                  })) ?? []),
                ]}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select Publisher" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  {publishersData?.map((pub: Publisher) => (
                    <SelectItem key={pub.id} value={String(pub.id)}>
                      {pub.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="category_id">Category</Label>
              <Select
                value={selectedCategoryId || null}
                onValueChange={(value: string | null) =>
                  setValue("category_id", value === "none" ? "" : value ?? "")
                }
                items={[
                  { value: "none", label: "None" },
                  ...(categoriesData?.map((cat: Category) => ({
                    value: String(cat.id),
                    label: cat.name,
                  })) ?? []),
                ]}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select Category" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  {categoriesData?.map((cat: Category) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="edition">Edition</Label>
              <Input id="edition" {...register("edition")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="language">Language</Label>
              <Input id="language" {...register("language")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="purchase_price">Purchase Price *</Label>
              <Input
                id="purchase_price"
                type="number"
                step="0.01"
                min="0"
                {...register("purchase_price")}
              />
              {errors.purchase_price && (
                <p className="text-sm text-destructive">
                  {errors.purchase_price.message}
                </p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="selling_price">Selling Price *</Label>
              <Input
                id="selling_price"
                type="number"
                step="0.01"
                min="0"
                {...register("selling_price")}
              />
              {errors.selling_price && (
                <p className="text-sm text-destructive">
                  {errors.selling_price.message}
                </p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="minimum_stock">Minimum Stock *</Label>
              <Input
                id="minimum_stock"
                type="number"
                min="0"
                {...register("minimum_stock")}
              />
              {errors.minimum_stock && (
                <p className="text-sm text-destructive">
                  {errors.minimum_stock.message}
                </p>
              )}
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label>Cover Image</Label>
              <div className="flex items-start gap-4">
                <div
                  className="flex flex-col items-center justify-center border-2 border-dashed rounded-lg p-4 cursor-pointer hover:border-primary transition-colors size-40"
                  onClick={() => fileInputRef.current?.click()}
                >
                  {coverPreview ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={coverPreview}
                      alt="Cover preview"
                      className="size-full object-contain rounded"
                    />
                  ) : (
                    <div className="flex flex-col items-center gap-1 text-muted-foreground">
                      <Upload className="size-8" />
                      <span className="text-xs text-center">Click to upload</span>
                    </div>
                  )}
                </div>
                {coverPreview && (
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={handleRemoveCover}
                  >
                    <X className="size-4" />
                  </Button>
                )}
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  className="hidden"
                  onChange={handleCoverUpload}
                />
                {isUploading && (
                  <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Loader2 className="size-4 animate-spin" />
                    Uploading...
                  </div>
                )}
                {uploadError && (
                  <p className="text-sm text-destructive">{uploadError}</p>
                )}
              </div>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Description</Label>
              <Textarea id="description" rows={4} {...register("description")} />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Authors *</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="mb-3 relative">
              <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Search authors..."
                className="pl-8"
                value={authorSearch}
                onChange={(e) => setAuthorSearch(e.target.value)}
              />
            </div>
            {errors.authors && (
              <p className="text-sm text-destructive mb-3">
                {errors.authors.message}
              </p>
            )}
            <div className="max-h-60 overflow-y-auto space-y-1 border rounded-lg p-2">
              {filteredAuthors?.length === 0 && (
                <p className="text-sm text-muted-foreground py-2">
                  No authors found.
                </p>
              )}
              {filteredAuthors?.map((author: Author) => (
                <label
                  key={author.id}
                  className="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-accent cursor-pointer"
                >
                  <input
                    type="checkbox"
                    className="size-4 rounded border-muted-foreground"
                    checked={selectedAuthorIds?.includes(author.id) ?? false}
                    onChange={() => toggleAuthor(author.id)}
                  />
                  <span className="text-sm">{author.name}</span>
                </label>
              ))}
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/books")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={isSubmitting || isUploading}>
            {isSubmitting && <Loader2 className="animate-spin" />}
            {id ? "Update Book" : "Create Book"}
          </Button>
        </div>
      </div>
    </form>
  );
}
