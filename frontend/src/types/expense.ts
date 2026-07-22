export interface ExpenseCategory {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  updated_by: { id: number; first_name: string; last_name: string; email: string } | null;
  created_at: string;
  updated_at: string;
}

export interface Expense {
  id: number;
  expense_no: string;
  expense_date: string;
  category: ExpenseCategory | null;
  payment_method: string;
  reference_no: string | null;
  amount: string;
  vendor_name: string;
  remarks: string | null;
  attachment: string | null;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  updated_by: { id: number; first_name: string; last_name: string; email: string } | null;
  created_at: string;
  updated_at: string;
}

export interface ExpenseFormData {
  expense_date: string;
  category_id: string;
  payment_method: string;
  reference_no: string;
  vendor_name: string;
  amount: string;
  remarks: string;
  attachment: File | null;
}

export interface ExpenseCategoryFormData {
  name: string;
  description: string;
  is_active: boolean;
}

export interface ExpenseListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  category_id?: string;
  payment_method?: string;
  date_from?: string;
  date_to?: string;
}

export interface ExpenseCategoryListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
}
