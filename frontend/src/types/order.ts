export type OrderStatus =
  | "intake"
  | "to_procure"
  | "to_pack"
  | "packed"
  | "dispatched"
  | "delivered"
  | "rto"
  | "cancelled";

export type OrderAvailabilityStatus =
  | "fully_available"
  | "partially_available"
  | "unavailable"
  | "unverifiable";

export interface Order {
  id: number;
  order_serial: string;
  customer: {
    id: number;
    name: string;
    company_name: string | null;
    email: string | null;
    phone: string;
    billing_address: string;
    city: string;
    state: string;
    gst_number: string | null;
  } | null;
  order_source: string;
  order_date: string;
  expected_delivery_date: string | null;
  subtotal: string;
  discount_amount: string;
  tax_amount: string;
  grand_total: string;
  currency: string | null;
  exchange_rate: string | null;
  status: OrderStatus | null;
  pre_book: boolean;
  reference_notes: string | null;
  notes: string | null;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  approved_by: { id: number; first_name: string; last_name: string; email: string } | null;
  approved_at: string | null;
  confirmed_at: string | null;
  pdf_generated_at: string | null;
  items: OrderItem[];
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  source_type: string;
  book_id: number | null;
  item_no: number;
  description: string;
  unit: string;
  ordered_quantity: string;
  remaining_order_quantity: string;
  unit_price: string;
  discount_percentage: string;
  discount_amount: string;
  tax_percentage: string;
  tax_amount: string;
  line_total: string;
  remarks: string | null;
  sort_order: number;
}

export interface OrderFormData {
  customer_id: string;
  order_date: string;
  expected_delivery_date: string;
  reference_notes: string;
  notes: string;
  pre_book: boolean;
  items: OrderItemFormData[];
}

export interface OrderItemFormData {
  book_id: string;
  source_type: string;
  description: string;
  unit: string;
  ordered_quantity: string;
  unit_price: string;
  discount_percentage: string;
  tax_percentage: string;
  remarks: string;
}

export interface OrderListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  status?: string;
  customer_id?: string;
  date_from?: string;
  date_to?: string;
}

export interface OrderAvailabilityItem {
  order_item_ids: number[];
  book_id: number | null;
  book_title: string | null;
  required_quantity: number;
  available_quantity: number;
  shortage_quantity: number;
  is_available: boolean;
  unverifiable?: boolean;
}

export interface OrderAvailability {
  order_id: number;
  status: OrderAvailabilityStatus;
  fully_available: boolean;
  items: OrderAvailabilityItem[];
}

export interface OrderStatusTransitionResponse {
  success: boolean;
  message: string;
  data: Order;
}

export interface OrderStatusHistory {
  id: number;
  from_status: OrderStatus | null;
  to_status: OrderStatus;
  changed_by: { id: number; name: string } | null;
  reason: string | null;
  created_at: string;
}

export interface OrderReservation {
  id: number;
  product_id: number;
  required_quantity: number;
  reserved_quantity: number;
  status: "allocated" | "waiting" | "consumed" | "released";
}

export interface OrderComment {
  id: number;
  order_id: number;
  user: { id: number; name: string } | null;
  comment: string;
  created_at: string;
  updated_at: string;
}
