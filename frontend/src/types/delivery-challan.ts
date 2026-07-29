export interface DeliveryChallan {
  id: number;
  delivery_challan_serial: string;
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
  delivery_date: string;
  delivery_address: string;
  transport_name: string | null;
  vehicle_number: string | null;
  driver_name: string | null;
  driver_mobile: string | null;
  lr_number: string | null;
  subtotal: string;
  discount_amount: string;
  tax_amount: string;
  grand_total: string;
  currency: string | null;
  exchange_rate: string | null;
  status: string;
  delivery_by: string | null;
  receiver_name: string | null;
  remarks: string | null;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  approved_by: { id: number; first_name: string; last_name: string; email: string } | null;
  approved_at: string | null;
  confirmed_at: string | null;
  pdf_generated_at: string | null;
  items: DeliveryChallanItem[];
  created_at: string;
  updated_at: string;
}

export interface DeliveryChallanItem {
  id: number;
  delivery_challan_id: number;
  order_id: number;
  order_item_id: number;
  source_type: string;
  item_no: number;
  description: string;
  unit: string;
  ordered_quantity: string;
  delivery_quantity: string;
  unit_price: string;
  line_total: string;
  remarks: string | null;
  sort_order: number;
  order_serial?: string;
}

export interface DeliveryChallanFormData {
  delivery_date: string;
  delivery_address: string;
  transport_name: string;
  vehicle_number: string;
  driver_name: string;
  driver_mobile: string;
  lr_number: string;
  status: string;
  delivery_by: string;
  receiver_name: string;
  remarks: string;
  items: DeliveryChallanItemFormData[];
}

export interface DeliveryChallanItemFormData {
  order_booking_item_id: string;
  order_booking_id: string;
  source_type: string;
  description: string;
  unit: string;
  ordered_quantity: string;
  delivery_quantity: string;
  unit_price: string;
  remarks: string;
  order_serial?: string;
}

export interface DeliveryChallanListParams {
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
