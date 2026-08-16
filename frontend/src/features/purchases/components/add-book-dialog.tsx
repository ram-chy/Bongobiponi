"use client";

import { Controller, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Loader2, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { bookService } from "@/services/book-service";
import { mapValidationErrors } from "@/lib/api-errors";

const addBookSchema = z.object({
  title: z.string().min(1, "Title is required").max(255),
  isbn: z.string().max(20).optional().or(z.literal("")),
  publisher_id: z.string().optional().or(z.literal("")),
  edition: z.string().max(50).optional().or(z.literal("")),
  language: z.string().max(50).optional().or(z.literal("")),
  purchase_price: z.string().optional().or(z.literal("")),
  selling_price: z.string().optional().or(z.literal("")),
});

type AddBookFormData = z.infer<typeof addBookSchema>;

interface AddBookDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  publishers: { id: number; name: string }[];
  onCreated: (book: { id: number; title: string; purchase_price: number }) => void;
}

export function AddBookDialog({
  open,
  onOpenChange,
  publishers,
  onCreated,
}: AddBookDialogProps) {
  const {
    register,
    handleSubmit,
    control,
    setError,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<AddBookFormData>({
    resolver: zodResolver(addBookSchema),
    defaultValues: {
      title: "",
      isbn: "",
      publisher_id: "",
      edition: "",
      language: "",
      purchase_price: "",
      selling_price: "",
    },
  });

  const onSubmit = async (data: AddBookFormData) => {
    const payload = {
      title: data.title,
      isbn: data.isbn || null,
      publisher_id: data.publisher_id ? Number(data.publisher_id) : null,
      edition: data.edition || null,
      language: data.language || null,
      purchase_price: data.purchase_price ? Number(data.purchase_price) : 0,
      selling_price: data.selling_price ? Number(data.selling_price) : 0,
    };

    try {
      const response = await bookService.create(payload);
      const created = response.data.data;
      onCreated({
        id: created.id,
        title: created.title,
        purchase_price: Number(created.purchase_price),
      });
      reset();
      onOpenChange(false);
    } catch (error) {
      mapValidationErrors(error, setError);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Add New Book</DialogTitle>
          <DialogDescription>
            Add a book to the catalogue directly from this bill entry screen.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="grid gap-4">
          <div className="space-y-2">
            <Label htmlFor="add-book-title">Book Name *</Label>
            <Input id="add-book-title" placeholder="Book title" {...register("title")} />
            {errors.title && (
              <p className="text-sm text-destructive">{errors.title.message}</p>
            )}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="add-book-isbn">ISBN</Label>
              <Input id="add-book-isbn" placeholder="ISBN" {...register("isbn")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="add-book-publisher">Publisher</Label>
              <Controller
                name="publisher_id"
                control={control}
                render={({ field }) => (
                  <Select
                    value={field.value || null}
                    onValueChange={(value) => field.onChange(value ?? "")}
                  >
                    <SelectTrigger id="add-book-publisher">
                      <SelectValue placeholder="Select publisher" />
                    </SelectTrigger>
                    <SelectContent>
                      {publishers.map((publisher) => (
                        <SelectItem key={publisher.id} value={publisher.id.toString()}>
                          {publisher.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="add-book-purchase-price">Purchase Price</Label>
              <Input
                id="add-book-purchase-price"
                type="number"
                min="0"
                step="0.01"
                {...register("purchase_price")}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="add-book-selling-price">Selling Price</Label>
              <Input
                id="add-book-selling-price"
                type="number"
                min="0"
                step="0.01"
                {...register("selling_price")}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="add-book-edition">Edition</Label>
              <Input id="add-book-edition" {...register("edition")} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="add-book-language">Language</Label>
              <Input id="add-book-language" {...register("language")} />
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={isSubmitting}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <Plus className="size-4" />
              )}
              Add Book
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
