export interface InventoryTransaction {
  id: number;
  transaction_no: string;
  transaction_type: string;
  transaction_type_label: string;
  reference_type: string | null;
  reference_id: number | null;
  book: {
    id: number;
    title: string;
    isbn: string;
  } | null;
  book_id: number;
  quantity_in: number;
  quantity_out: number;
  balance_after: number;
  transaction_date: string;
  remarks: string | null;
  created_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface Stock {
  id: number;
  book: {
    id: number;
    title: string;
    isbn: string;
    purchase_price: number;
    minimum_stock: number;
    category?: { id: number; name: string } | null;
    publisher?: { id: number; name: string } | null;
  } | null;
  book_id: number;
  current_quantity: number;
  last_transaction_id: number | null;
  created_at: string;
  updated_at: string;
}

export type StockStatus = "in_stock" | "low_stock" | "out_of_stock";
