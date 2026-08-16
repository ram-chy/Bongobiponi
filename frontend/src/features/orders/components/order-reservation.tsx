import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useOrderReservations } from "@/features/orders/hooks/use-order-reservations";
import { Loader2 } from "lucide-react";

interface OrderReservationProps {
  orderId: number;
}

export function OrderReservation({ orderId }: OrderReservationProps) {
  const { data: reservations, isLoading } = useOrderReservations(orderId);

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Stock Allocation</CardTitle>
        </CardHeader>
        <CardContent className="flex items-center justify-center py-8">
          <Loader2 className="size-6 animate-spin text-muted-foreground" />
        </CardContent>
      </Card>
    );
  }

  if (!reservations || reservations.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Stock Allocation</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">No reservation information available.</p>
        </CardContent>
      </Card>
    );
  }

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "allocated":
        return (
          <span className="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
            Allocated
          </span>
        );
      case "waiting":
        return (
          <span className="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700">
            Waiting for Stock
          </span>
        );
      case "consumed":
        return (
          <span className="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
            Consumed
          </span>
        );
      case "released":
        return (
          <span className="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
            Released
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700">
            {status}
          </span>
        );
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Stock Allocation</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto rounded-lg border">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="p-2 text-left font-medium">Product</th>
                <th className="p-2 text-right font-medium">Required</th>
                <th className="p-2 text-right font-medium">Reserved</th>
                <th className="p-2 text-left font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              {reservations.map((reservation) => (
                <tr key={reservation.id} className="border-b last:border-0">
                  <td className="p-2 font-medium">Product #{reservation.product_id}</td>
                  <td className="p-2 text-right">{reservation.required_quantity}</td>
                  <td className="p-2 text-right">{reservation.reserved_quantity}</td>
                  <td className="p-2">{getStatusBadge(reservation.status)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}