import { useQuery } from "@tanstack/react-query";
import apiClient from "@/lib/axios";

interface OrderItem {
  id: number;
  description: string;
  unit: string;
  ordered_quantity: string;
  remaining_order_quantity: string;
  unit_price: string;
  discount_percentage: string;
  tax_percentage: string;
  sort_order: number;
}

interface Order {
  id: number;
  order_serial: string;
  order_date: string;
  status: string;
  items: OrderItem[];
}

interface OrdersResponse {
  data: Order[];
}

export function useCustomerOrders(customerId: number | null) {
  return useQuery({
    queryKey: ["/orders", "customer", customerId],
    queryFn: async () => {
      if (!customerId) return [];
      const response = await apiClient.get<OrdersResponse>("/orders", {
        params: { customer_id: customerId, per_page: 100 },
      });
      return response.data.data ?? [];
    },
    enabled: !!customerId,
  });
}
