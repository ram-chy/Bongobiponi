export interface ReceiveOrderItem {
  id: number;
  receive_order_id: number;
  book: {
    id: number;
    title: string;
    isbn: string;
  } | null;
  book_id: number;
  ordered_quantity: number;
  received_quantity: number;
  purchase_price: number;
  discount_percentage: number;
  tax_percentage: number;
  remarks: string | null;
  created_at: string;
  updated_at: string;
}

export interface ReceiveOrder {
  id: number;
  order_no: string;
  supplier: {
    id: number;
    name: string;
    company_name: string;
  } | null;
  supplier_id: number;
  customer: {
    id: number;
    name: string;
    company_name: string;
  } | null;
  customer_id: number | null;
  expected_delivery_date: string;
  reference_no: string | null;
  notes: string | null;
  status: "draft" | "approved" | "partially_received" | "completed" | "cancelled";
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
  items: ReceiveOrderItem[];
  total_amount: number;
  created_at: string;
  updated_at: string;
}
