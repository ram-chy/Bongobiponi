"use client";

import { useMemo } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { AlertTriangle, ArrowLeft, Loader2 } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { PurchaseForm } from "@/features/purchases/components/purchase-form";
import { PageBreadcrumb } from "@/components/breadcrumb/page-breadcrumb";
import { PageHeader } from "@/components/page-header/page-header";
import { Button, buttonVariants } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { useOrder } from "@/features/orders/hooks/use-order";
import { useOrderAvailability } from "@/features/orders/hooks/use-order-availability";
import { bookService } from "@/services/book-service";
import type { Book } from "@/types/book";
import type { Purchase } from "@/types/purchase";
import { cn } from "@/lib/utils";

interface CreatePurchasePageProps {
  orderId?: number;
}

export function CreatePurchasePage({ orderId }: CreatePurchasePageProps) {
  const router = useRouter();

  const { data: order, isLoading: orderLoading, isError: orderError } = useOrder(orderId ?? 0);
  const {
    data: availability,
    isLoading: availabilityLoading,
    isError: availabilityError,
  } = useOrderAvailability(orderId ?? 0);

  const { data: booksData, isLoading: booksLoading } = useQuery({
    queryKey: ["/books"],
    queryFn: async () => {
      const response = await bookService.list({ per_page: 1000 });
      return response.data.data as Book[];
    },
    enabled: !!orderId,
  });

  const procurementItems = useMemo(() => {
    if (!orderId || !availability) return [];
    return availability.items.filter(
      (item) => !item.unverifiable && item.book_id != null && item.shortage_quantity > 0
    );
  }, [orderId, availability]);

  const defaultValues = useMemo<Partial<Purchase>>(() => {
    if (!orderId || procurementItems.length === 0) return {};

    const books = booksData ?? [];

    return {
      purchase_date: new Date().toISOString().split("T")[0],
      items: procurementItems.map((item) => {
        const book = books.find((b) => b.id === item.book_id);
        return {
          book_id: item.book_id as number,
          ordered_quantity: item.shortage_quantity,
          received_quantity: item.shortage_quantity,
          purchase_price: book?.purchase_price ?? 0,
          remarks: "",
        } as Purchase["items"][number];
      }),
    };
  }, [orderId, procurementItems, booksData]);

  const loading = orderId
    ? orderLoading || availabilityLoading || booksLoading
    : false;

  const header = (
    <>
      <PageBreadcrumb
        items={[
          { label: "Purchases" },
          { label: "Purchases", href: "/purchases" },
          { label: orderId ? "Procurement" : "Create" },
        ]}
      />
      <PageHeader
        title={orderId ? "Procurement Purchase" : "Create Purchase"}
        description={
          orderId
            ? "Create a purchase to cover the shortage items for this order."
            : "Create a new purchase record."
        }
      />
    </>
  );

  if (loading) {
    return (
      <div>
        {header}
        <div className="flex items-center justify-center py-20">
          <Loader2 className="size-8 animate-spin text-muted-foreground" />
        </div>
      </div>
    );
  }

  if (orderId && (orderError || availabilityError)) {
    return (
      <div>
        {header}
        <Card>
          <CardContent className="flex items-center gap-2 py-8 text-sm text-destructive">
            <AlertTriangle className="size-4" />
            Procurement could not be started because order or availability data could not be loaded.
          </CardContent>
        </Card>
        <div className="mt-4">
          <Link
            href={`/orders/${orderId}`}
            className={cn(buttonVariants({ variant: "ghost" }), "gap-1.5")}
          >
            <ArrowLeft className="size-4" />
            Return to Order
          </Link>
        </div>
      </div>
    );
  }

  if (orderId && availability?.fully_available) {
    return (
      <div>
        {header}
        <Card>
          <CardContent className="py-8">
            <p className="text-sm text-emerald-600 dark:text-emerald-400">
              All required items are currently available.
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
              No procurement is needed for order {order?.order_serial}. Use the Order
              status actions to move it forward.
            </p>
          </CardContent>
        </Card>
        <div className="mt-4">
          <Link
            href={`/orders/${orderId}`}
            className={cn(buttonVariants({ variant: "ghost" }), "gap-1.5")}
          >
            <ArrowLeft className="size-4" />
            Return to Order
          </Link>
        </div>
      </div>
    );
  }

  if (orderId && procurementItems.length === 0) {
    return (
      <div>
        {header}
        <Card>
          <CardContent className="py-8">
            <p className="text-sm text-amber-600 dark:text-amber-400">
              No procurable shortage items were found for this order.
            </p>
            {availability?.items.some((item) => item.unverifiable) && (
              <p className="mt-1 text-sm text-muted-foreground">
                One or more items cannot be verified and cannot be procured because their
                Book reference is missing.
              </p>
            )}
          </CardContent>
        </Card>
        <div className="mt-4">
          <Link
            href={`/orders/${orderId}`}
            className={cn(buttonVariants({ variant: "ghost" }), "gap-1.5")}
          >
            <ArrowLeft className="size-4" />
            Return to Order
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div>
      {header}

      {orderId && order && (
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>Procurement Requested For</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-wrap items-center justify-between gap-3">
            <div className="text-sm">
              <p className="font-medium">
                Order {order.order_serial}
                {order.customer?.company_name && (
                  <span className="text-muted-foreground">
                    {" "}
                    — {order.customer.company_name}
                  </span>
                )}
              </p>
              <p className="text-muted-foreground">
                Suggested quantities are based on current availability. You may adjust them.
              </p>
            </div>
            <div className="flex items-center gap-2">
              <Button type="button" variant="outline" onClick={() => router.push(`/orders/${orderId}`)}>
                <ArrowLeft className="size-4" />
                Return to Order
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      <PurchaseForm
        defaultValues={orderId ? defaultValues : undefined}
        onCreated={(purchaseId) => {
          router.push(
            orderId
              ? `/purchases/${purchaseId}?from_order=${orderId}`
              : `/purchases/${purchaseId}`
          );
        }}
        backHref={orderId ? `/orders/${orderId}` : undefined}
        backLabel={orderId ? "Return to Order" : undefined}
      />
    </div>
  );
}
