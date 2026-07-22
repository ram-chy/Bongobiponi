"use client";

import { useEffect, useState, useRef } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { Loader2, ArrowLeft, Upload, X, FileIcon } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useExpenseForm } from "@/features/expenses/hooks/use-expense-form";
import { useExpenseCategoryList } from "@/features/expenses/hooks/use-expense-category-list";
import { mapValidationErrors } from "@/lib/api-errors";
import type { Expense, ExpenseCategory } from "@/types/expense";

const expenseSchema = z.object({
  expense_date: z.string().min(1, "Date is required"),
  category_id: z.string().min(1, "Category is required"),
  payment_method: z.string().min(1, "Payment method is required"),
  reference_no: z.string().optional().or(z.literal("")),
  vendor_name: z.string().min(1, "Vendor name is required"),
  amount: z.string().min(1, "Amount is required").refine((v) => {
    const n = parseFloat(v);
    return !isNaN(n) && n > 0;
  }, "Amount must be greater than 0"),
  remarks: z.string().optional().or(z.literal("")),
});

type ExpenseFormValues = z.infer<typeof expenseSchema>;

interface ExpenseFormProps {
  defaultValues?: Partial<ExpenseFormValues & { attachment: string | null }>;
  id?: number;
  expense?: Expense;
}

export function ExpenseForm({ defaultValues, id, expense }: ExpenseFormProps) {
  const router = useRouter();
  const mutation = useExpenseForm({ id });

  const [attachment, setAttachment] = useState<File | null>(null);
  const [attachmentPreview, setAttachmentPreview] = useState<string | null>(
    defaultValues?.attachment ?? null
  );
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { data: categoriesData } = useExpenseCategoryList({ per_page: 100 });
  const categories: ExpenseCategory[] = (categoriesData?.data ?? []).filter(
    (c: ExpenseCategory) => c.is_active
  );
  const categoryItems = categories.map((cat) => ({
    value: String(cat.id),
    label: cat.name,
  }));

  const {
    register,
    handleSubmit,
    setError,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<ExpenseFormValues>({
    resolver: zodResolver(expenseSchema),
    defaultValues: {
      expense_date: new Date().toISOString().split("T")[0],
      category_id: "",
      payment_method: "",
      reference_no: "",
      vendor_name: "",
      amount: "",
      remarks: "",
      ...defaultValues,
    },
  });

  useEffect(() => {
    if (defaultValues) {
      const { attachment: _, ...rest } = defaultValues;
      void _;
      reset(rest);
    }
  }, [defaultValues, reset]);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setAttachment(file);
      setAttachmentPreview(null);
    }
  };

  const removeAttachment = () => {
    setAttachment(null);
    setAttachmentPreview(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  };

  const onSubmit = async (data: ExpenseFormValues) => {
    try {
      const formData = new FormData();
      formData.append("expense_date", data.expense_date);
      formData.append("category_id", data.category_id);
      formData.append("payment_method", data.payment_method);
      formData.append("vendor_name", data.vendor_name);
      formData.append("amount", data.amount);
      if (data.reference_no) formData.append("reference_no", data.reference_no);
      if (data.remarks) formData.append("remarks", data.remarks);
      if (attachment) formData.append("attachment", attachment);
      if (id) formData.append("_method", "PUT");

      await mutation.mutateAsync(formData);
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
          onClick={() => router.push("/expenses")}
          className="gap-1.5"
        >
          <ArrowLeft className="size-4" />
          Back to Expenses
        </Button>
      </div>

      <div className="grid gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Expense Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="expense_date">Date *</Label>
                <input
                  id="expense_date"
                  type="date"
                  {...register("expense_date")}
                  className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-base transition-colors focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 md:text-sm"
                />
                {errors.expense_date && (
                  <p className="text-sm text-destructive">{errors.expense_date.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="category_id">Category *</Label>
                <Select
                  value={watch("category_id") || null}
                  onValueChange={(v) => setValue("category_id", v ?? "")}
                  items={categoryItems}
                >
                  <SelectTrigger id="category_id">
                    <SelectValue placeholder="Select category..." />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((cat) => (
                      <SelectItem key={cat.id} value={String(cat.id)}>
                        {cat.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.category_id && (
                  <p className="text-sm text-destructive">{errors.category_id.message}</p>
                )}
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="vendor_name">Vendor Name *</Label>
                <Input id="vendor_name" placeholder="Vendor name..." {...register("vendor_name")} />
                {errors.vendor_name && (
                  <p className="text-sm text-destructive">{errors.vendor_name.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="amount">Amount *</Label>
                <Input
                  id="amount"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  {...register("amount")}
                />
                {errors.amount && (
                  <p className="text-sm text-destructive">{errors.amount.message}</p>
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Payment Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="payment_method">Payment Method *</Label>
                <Select
                  value={watch("payment_method") || null}
                  onValueChange={(v) => setValue("payment_method", v ?? "")}
                  items={[
                    { value: "Cash", label: "Cash" },
                    { value: "Bank Transfer", label: "Bank Transfer" },
                    { value: "UPI", label: "UPI" },
                    { value: "Cheque", label: "Cheque" },
                  ]}
                >
                  <SelectTrigger id="payment_method">
                    <SelectValue placeholder="Select method..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Cash">Cash</SelectItem>
                    <SelectItem value="Bank Transfer">Bank Transfer</SelectItem>
                    <SelectItem value="UPI">UPI</SelectItem>
                    <SelectItem value="Cheque">Cheque</SelectItem>
                  </SelectContent>
                </Select>
                {errors.payment_method && (
                  <p className="text-sm text-destructive">{errors.payment_method.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="reference_no">Reference No</Label>
                <Input
                  id="reference_no"
                  placeholder="Cheque / Transaction ref..."
                  {...register("reference_no")}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Attachment</CardTitle>
          </CardHeader>
          <CardContent>
            {attachment ? (
              <div className="flex items-center gap-2 rounded-lg border p-3">
                <FileIcon className="size-4 text-muted-foreground" />
                <span className="flex-1 truncate text-sm">{attachment.name}</span>
                <Button type="button" variant="ghost" size="icon-sm" onClick={removeAttachment}>
                  <X className="size-4" />
                </Button>
              </div>
            ) : attachmentPreview ? (
              <div className="space-y-2">
                <div className="flex items-center gap-2 rounded-lg border p-3">
                  <FileIcon className="size-4 text-muted-foreground" />
                  <span className="flex-1 truncate text-sm">Current attachment</span>
                  <Button type="button" variant="ghost" size="icon-sm" onClick={removeAttachment}>
                    <X className="size-4" />
                  </Button>
                </div>
              </div>
            ) : (
              <div
                className="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed p-6 text-center hover:bg-muted/50"
                onClick={() => fileInputRef.current?.click()}
              >
                <Upload className="size-8 text-muted-foreground" />
                <p className="text-sm text-muted-foreground">
                  Click to upload or drag and drop
                </p>
                <p className="text-xs text-muted-foreground">
                  PDF, JPG, PNG up to 10MB
                </p>
              </div>
            )}
            <input
              ref={fileInputRef}
              type="file"
              className="hidden"
              accept=".pdf,.jpg,.jpeg,.png"
              onChange={handleFileChange}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Remarks</CardTitle>
          </CardHeader>
          <CardContent>
            <Textarea rows={3} {...register("remarks")} placeholder="Additional notes..." />
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-2 pb-8">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push("/expenses")}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={mutation.isPending}>
            {mutation.isPending && <Loader2 className="animate-spin" />}
            {id ? "Update Expense" : "Create Expense"}
          </Button>
        </div>
      </div>
    </form>
  );
}
