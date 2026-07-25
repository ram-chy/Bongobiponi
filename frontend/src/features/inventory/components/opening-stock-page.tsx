"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
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
import { Loader2, ArrowLeft } from "lucide-react";
import { inventoryService } from "@/services/inventory-service";
import { bookService } from "@/services/book-service";
import { toast } from "sonner";
import { AxiosError } from "axios";

export function OpeningStockPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [bookId, setBookId] = useState("");
  const [quantity, setQuantity] = useState("");
  const [transactionDate, setTransactionDate] = useState(
    new Date().toISOString().split("T")[0]
  );
  const [remarks, setRemarks] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});

  const { data: booksData, isLoading: booksLoading } = useQuery({
    queryKey: ["/books"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data;
    },
  });

  const mutation = useMutation({
    mutationFn: (data: Record<string, unknown>) =>
      inventoryService.createOpening(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/inventory"] });
      toast.success("Opening stock recorded successfully");
      router.push("/inventory/stock");
    },
    onError: (error) => {
      if (error instanceof AxiosError && error.response?.status === 422) {
        const validationErrors = error.response.data?.errors ?? {};
        const mapped: Record<string, string> = {};
        Object.entries(validationErrors).forEach(([key, msgs]) => {
          mapped[key] = (msgs as string[])[0];
        });
        setErrors(mapped);
      } else {
        toast.error("An unexpected error occurred");
      }
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    const parsedBookId = parseInt(bookId);
    const parsedQuantity = parseInt(quantity);
    const newErrors: Record<string, string> = {};
    if (isNaN(parsedBookId)) newErrors.book_id = "Please select a book";
    if (isNaN(parsedQuantity) || parsedQuantity <= 0) newErrors.quantity = "Quantity must be a positive number";
    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    mutation.mutate({
      book_id: parsedBookId,
      quantity: parsedQuantity,
      transaction_date: transactionDate,
      remarks: remarks || null,
    });
  };

  if (booksLoading) {
    return (
      <div className="flex items-center justify-center py-20 text-muted-foreground">
        Loading...
      </div>
    );
  }

  const books = booksData ?? [];

  return (
    <div>
      <PageBreadcrumb
        items={[
          { label: "Inventory" },
          { label: "Opening Stock", href: "/inventory/opening" },
          { label: "Create" },
        ]}
      />
      <PageHeader title="Opening Stock" description="Set initial stock for a book." />

      <form onSubmit={handleSubmit}>
        <div className="mb-6">
          <Button
            type="button"
            variant="ghost"
            onClick={() => router.push("/inventory/stock")}
          >
            <ArrowLeft className="size-4" />
            Back
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Opening Stock Details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Book *</Label>
              <Select
                value={bookId || null}
                onValueChange={(v) => setBookId(v ?? "")}
                items={books.map((book: { id: number; title: string }) => ({ value: book.id.toString(), label: book.title }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select book" />
                </SelectTrigger>
                <SelectContent>
                  {books.map((book: { id: number; title: string }) => (
                    <SelectItem key={book.id} value={book.id.toString()}>
                      {book.title}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.book_id && (
                <p className="text-sm text-destructive">{errors.book_id}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="quantity">Quantity *</Label>
              <Input
                id="quantity"
                type="number"
                min="1"
                value={quantity}
                onChange={(e) => setQuantity(e.target.value)}
              />
              {errors.quantity && (
                <p className="text-sm text-destructive">{errors.quantity}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="transaction_date">Transaction Date *</Label>
              <Input
                id="transaction_date"
                type="date"
                value={transactionDate}
                onChange={(e) => setTransactionDate(e.target.value)}
              />
              {errors.transaction_date && (
                <p className="text-sm text-destructive">{errors.transaction_date}</p>
              )}
            </div>

            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="remarks">Remarks</Label>
              <Textarea
                id="remarks"
                value={remarks}
                onChange={(e) => setRemarks(e.target.value)}
              />
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 mt-6 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/inventory/stock")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={mutation.isPending}>
            {mutation.isPending && <Loader2 className="animate-spin" />}
            Record Opening Stock
          </Button>
        </div>
      </form>
    </div>
  );
}
