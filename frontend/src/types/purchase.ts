export interface PurchaseItem {
  id: number;
  purchase_id: number;
  book: {
    id: number;
    title: string;
    isbn: string;
  } | null;
  book_id: number;
  ordered_quantity: number;
  received_quantity: number;
  purchase_price: number;
  total: number;
  remarks: string | null;
  created_at: string;
  updated_at: string;
}

export interface Purchase {
  id: number;
  purchase_no: string;
  purchase_type: string;
  supplier: {
    id: number;
    name: string;
    company_name: string;
  } | null;
  supplier_id: number;
  invoice_no: string | null;
  invoice_date: string | null;
  purchase_date: string;
  notes: string | null;
  status: "draft" | "confirmed" | "cancelled";
  total_amount: number;
  created_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  updated_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  items: PurchaseItem[];
  created_at: string;
  updated_at: string;
}
