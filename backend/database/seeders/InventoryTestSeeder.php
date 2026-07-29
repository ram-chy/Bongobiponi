<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Publisher;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class InventoryTestSeeder extends Seeder
{
    use WithoutModelEvents;

    private User $admin;
    private int $count = 100;
    private int $orderOffset = 0;
    private int $deliveryChallanOffset = 0;
    private int $invoiceOffset = 0;
    private int $paymentOffset = 0;
    private int $purchaseOffset = 0;
    private int $expenseOffset = 0;
    private int $inventoryTransactionOffset = 0;

    public function run(): void
    {
        $this->orderOffset = Order::withoutGlobalScopes()->count();
        $this->deliveryChallanOffset = DeliveryChallan::withoutGlobalScopes()->count();
        $this->invoiceOffset = Invoice::withoutGlobalScopes()->count();
        $this->paymentOffset = Payment::withoutGlobalScopes()->count();
        $this->purchaseOffset = Purchase::withoutGlobalScopes()->count();
        $this->expenseOffset = Expense::withoutGlobalScopes()->count();
        $this->inventoryTransactionOffset = InventoryTransaction::withoutGlobalScopes()->count();
        $this->admin = User::firstOrCreate(
            ['email' => 'inventory-test@bongobiponi.com'],
            [
                'first_name' => 'Inventory',
                'last_name' => 'Tester',
                'mobile_no' => '9999999999',
                'password' => bcrypt('password'),
                'role_id' => 1,
                'email_verified_at' => now(),
            ]
        );

        $publishers = $this->seedPublishers();
        $categories = $this->seedCategories();
        $authors = $this->seedAuthors();
        $suppliers = $this->seedSuppliers();
        $books = $this->seedBooks($publishers, $categories, $authors);
        $customers = $this->seedCustomers();
        $expenseCategories = $this->seedExpenseCategories();

        $orders = $this->seedOrders($customers);
        $deliveryChallans = $this->seedDeliveryChallans($customers, $orders);
        $invoices = $this->seedInvoices($customers, $deliveryChallans, $orders);
        $this->seedPayments($customers, $invoices);

        $this->seedPurchases($suppliers, $books);

        $this->seedExpenses($expenseCategories);

        $this->seedStocks($books);
        $this->seedInventoryTransactions($books);

        $this->command->info("Inventory test seed completed! Seeded {$this->count}+ records per table.");
    }

    private function seedPublishers(): Collection
    {
        $publishers = collect();
        for ($i = 0; $i < $this->count; $i++) {
            $publishers->push(Publisher::create([
                'created_by' => $this->admin->id,
                'name' => 'Publisher ' . ($i + 1) . ' - ' . fake()->company(),
                'phone' => fake()->optional(0.8)->numerify('##########'),
                'email' => fake()->unique()->safeEmail(),
                'address' => fake()->address(),
                'remarks' => fake()->optional()->sentence(),
                'status' => true,
            ]));
        }
        return $publishers;
    }

    private function seedCategories(): Collection
    {
        $categories = collect();

        $parentNames = [
            'Fiction', 'Non-Fiction', 'Academic', 'Children & Young Adult',
            'Self-Help', 'Science & Technology', 'Comics & Graphic Novels',
            'Poetry', 'Drama', 'Reference', 'Religion & Spirituality',
            'Travel', 'Cooking', 'Art & Photography', 'Health & Fitness',
            'Business & Economics', 'Law', 'Education', 'Language', 'Social Sciences',
        ];

        for ($i = 0; $i < 20; $i++) {
            $categories->push(Category::create([
                'created_by' => $this->admin->id,
                'parent_id' => null,
                'name' => ($parentNames[$i] ?? 'Category ' . ($i + 1)),
                'description' => fake()->sentence(),
                'status' => true,
            ]));
        }

        $suffixes = ['General', 'Advanced', 'Beginner', 'Intermediate', 'Specialist', 'Applied', 'Theoretical', 'Practical'];
        for ($i = 0; $i < 80; $i++) {
            $categories->push(Category::create([
                'created_by' => $this->admin->id,
                'parent_id' => $categories->random()->id,
                'name' => 'Subcategory ' . ($i + 1) . ' - ' . $suffixes[$i % count($suffixes)],
                'description' => fake()->sentence(),
                'status' => true,
            ]));
        }

        return $categories;
    }

    private function seedAuthors(): Collection
    {
        $authors = collect();
        $countries = ['India', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Japan', 'Brazil', 'Nigeria'];

        for ($i = 0; $i < $this->count; $i++) {
            $authors->push(Author::create([
                'created_by' => $this->admin->id,
                'name' => 'Author ' . ($i + 1) . ' ' . fake()->lastName(),
                'biography' => fake()->optional(0.7)->paragraphs(2, true),
                'country' => $countries[array_rand($countries)],
                'remarks' => fake()->optional()->sentence(),
                'status' => true,
            ]));
        }
        return $authors;
    }

    private function seedSuppliers(): Collection
    {
        $suppliers = collect();
        for ($i = 0; $i < $this->count; $i++) {
            $suppliers->push(Supplier::create([
                'created_by' => $this->admin->id,
                'name' => 'Supplier ' . ($i + 1) . ' ' . fake()->lastName(),
                'company_name' => fake()->company() . ' Supplies',
                'phone' => fake()->unique()->numerify('##########'),
                'email' => fake()->boolean(80) ? fake()->unique()->safeEmail() : null,
                'gst_number' => fake()->boolean(70) ? fake()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}') : null,
                'address' => fake()->address(),
                'remarks' => fake()->optional()->sentence(),
                'status' => true,
            ]));
        }
        return $suppliers;
    }

    private function seedBooks(Collection $publishers, Collection $categories, Collection $authors): Collection
    {
        $books = collect();
        $languages = ['English', 'Hindi', 'Bengali', 'Tamil', 'Telugu', 'Marathi', 'Gujarati', 'Kannada'];
        $editions = ['1st', '2nd', '3rd', '4th', 'Revised', 'Extended', null];

        for ($i = 0; $i < $this->count; $i++) {
            $purchasePrice = fake()->randomFloat(2, 100, 2000);
            $sellingPrice = round($purchasePrice * fake()->randomFloat(2, 1.3, 2.5), 2);

            $book = Book::create([
                'created_by' => $this->admin->id,
                'isbn' => '978-' . fake()->unique()->numerify('#-##-######-#'),
                'barcode' => fake()->unique()->numerify('##############'),
                'title' => 'Book Title ' . ($i + 1) . ' - ' . fake()->words(2, true),
                'subtitle' => fake()->optional(0.4)->sentence(),
                'publisher_id' => $publishers->random()->id,
                'category_id' => $categories->random()->id,
                'edition' => $editions[array_rand($editions)],
                'language' => $languages[array_rand($languages)],
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'minimum_stock' => fake()->numberBetween(3, 20),
                'description' => fake()->optional(0.7)->paragraph(),
                'status' => true,
            ]);

            $authorCount = fake()->numberBetween(1, 3);
            $book->authors()->attach($authors->random($authorCount)->pluck('id')->toArray());

            $books->push($book);
        }
        return $books;
    }

    private function seedCustomers(): Collection
    {
        $customers = collect();
        $states = ['Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu', 'West Bengal', 'Gujarat', 'Rajasthan', 'Kerala', 'Telangana', 'Uttar Pradesh'];

        for ($i = 0; $i < $this->count; $i++) {
            $customers->push(Customer::create([
                'created_by' => $this->admin->id,
                'customer_code' => 'BBCU/' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->unique()->numerify('##########'),
                'alternate_phone' => fake()->optional(0.3)->numerify('##########'),
                'billing_address' => fake()->address(),
                'shipping_address' => fake()->optional(0.5)->address(),
                'city' => fake()->city(),
                'state' => $states[array_rand($states)],
                'country' => 'India',
                'postal_code' => fake()->numerify('######'),
                'notes' => fake()->optional()->sentence(),
            ]));
        }
        return $customers;
    }

    private function seedExpenseCategories(): Collection
    {
        $categories = collect();
        $names = [
            'Office Rent', 'Electricity', 'Internet', 'Fuel', 'Printing',
            'Salary', 'Stationery', 'Marketing', 'Miscellaneous', 'Transport',
            'Packaging', 'Maintenance', 'Insurance', 'Telephone', 'Water',
            'Security', 'Cleaning', 'Travel', 'Software', 'Hardware',
        ];

        for ($i = 0; $i < $this->count; $i++) {
            $name = $i < count($names) ? $names[$i] : 'Expense Category ' . ($i + 1);
            $categories->push(ExpenseCategory::create([
                'name' => $name . ' ' . ($i + 1),
                'description' => fake()->optional()->sentence(),
                'is_active' => true,
                'created_by' => $this->admin->id,
            ]));
        }
        return $categories;
    }

    private function seedOrders(Collection $customers): Collection
    {
        $orders = collect();
        for ($i = 0; $i < $this->count; $i++) {
            $order = Order::create([
                'order_serial' => 'BB/' . str_pad((string) ($this->orderOffset + $i + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
                'customer_id' => $customers->random()->id,
                'order_source' => fake()->randomElement(['manual', 'phone', 'email', 'walk-in']),
                'order_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'expected_delivery_date' => fake()->dateTimeBetween('+3 days', '+1 month')->format('Y-m-d'),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'currency' => 'INR',
                'exchange_rate' => 1,
                'status' => fake()->randomElement(['draft', 'confirmed', 'confirmed', 'completed']),
                'notes' => fake()->optional()->sentence(),
                'created_by' => $this->admin->id,
            ]);

            $subtotal = 0;
            $itemCount = fake()->numberBetween(1, 4);
            for ($j = 0; $j < $itemCount; $j++) {
                $qty = fake()->randomFloat(2, 1, 100);
                $unitPrice = fake()->randomFloat(2, 10, 5000);
                $discountPct = fake()->optional(0.3)->randomFloat(2, 0, 20) ?? 0;
                $baseAmount = $qty * $unitPrice;
                $discountAmt = $baseAmount * ($discountPct / 100);
                $taxPct = fake()->optional(0.5)->randomFloat(2, 0, 18) ?? 0;
                $taxAmt = ($baseAmount - $discountAmt) * ($taxPct / 100);
                $lineTotal = $baseAmount - $discountAmt + $taxAmt;

                OrderItem::create([
                    'order_id' => $order->id,
                    'source_type' => 'manual',
                    'item_no' => $j + 1,
                    'description' => fake()->sentence(3),
                    'unit' => fake()->randomElement(['pcs', 'kg', 'm', 'sqft']),
                    'ordered_quantity' => $qty,
                    'remaining_order_quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_percentage' => $discountPct,
                    'discount_amount' => $discountAmt,
                    'tax_percentage' => $taxPct,
                    'tax_amount' => $taxAmt,
                    'line_total' => $lineTotal,
                    'sort_order' => $j,
                ]);

                $subtotal += $lineTotal;
            }

            $discountTotal = $order->items->sum('discount_amount');
            $taxTotal = $order->items->sum('tax_amount');
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountTotal,
                'tax_amount' => $taxTotal,
                'grand_total' => $subtotal - $discountTotal + $taxTotal,
            ]);

            $orders->push($order);
        }
        return $orders;
    }

    private function seedDeliveryChallans(Collection $customers, Collection $orders): Collection
    {
        $deliveryChallans = collect();
        for ($i = 0; $i < $this->count; $i++) {
            $dc = DeliveryChallan::create([
                'serial' => 'BBDC/' . str_pad((string) ($this->deliveryChallanOffset + $i + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
                'delivery_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'customer_id' => $customers->random()->id,
                'delivery_address' => fake()->address(),
                'transport_name' => fake()->optional(0.6)->company(),
                'vehicle_number' => fake()->optional(0.5)->bothify('?? ####'),
                'driver_name' => fake()->optional(0.6)->name(),
                'driver_mobile' => fake()->optional(0.6)->numerify('##########'),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'status' => fake()->randomElement(['draft', 'dispatched', 'delivered']),
                'delivery_by' => fake()->optional()->name(),
                'receiver_name' => fake()->optional()->name(),
                'created_by' => $this->admin->id,
            ]);

            $subtotal = 0;
            $itemCount = fake()->numberBetween(1, 3);
            for ($j = 0; $j < $itemCount; $j++) {
                $order = $orders->random();
                $orderItem = $order->items->random();
                $qty = fake()->numberBetween(1, 50);
                $unitPrice = fake()->randomFloat(2, 10, 5000);

                $dcItem = DeliveryChallanItem::forceCreate([
                    'delivery_challan_id' => $dc->id,
                    'order_booking_id' => $order->id,
                    'order_booking_item_id' => $orderItem->id,
                    'item_description' => fake()->sentence(3),
                    'unit' => fake()->randomElement(['pcs', 'kg', 'm', 'sqft']),
                    'ordered_quantity' => $qty,
                    'delivered_quantity' => $qty,
                    'invoiced_quantity' => 0,
                    'remaining_invoice_quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'remarks' => fake()->optional()->sentence(),
                ]);

                $subtotal += $qty * $unitPrice;
            }

            $dc->update([
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
            ]);

            $deliveryChallans->push($dc);
        }
        return $deliveryChallans;
    }

    private function seedInvoices(Collection $customers, Collection $deliveryChallans, Collection $orders): Collection
    {
        $invoices = collect();
        for ($i = 0; $i < $this->count; $i++) {
            $invoice = Invoice::create([
                'serial' => 'BBINV/' . str_pad((string) ($this->invoiceOffset + $i + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
                'invoice_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'due_date' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
                'customer_id' => $customers->random()->id,
                'billing_address' => fake()->address(),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'round_off' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'payment_status' => 'Unpaid',
                'status' => fake()->randomElement(['draft', 'issued', 'issued', 'issued']),
                'created_by' => $this->admin->id,
            ]);

            $subtotal = 0;
            $itemCount = fake()->numberBetween(1, 3);
            for ($j = 0; $j < $itemCount; $j++) {
                $dc = $deliveryChallans->random();
                $dcItem = $dc->items->random();
                $order = $orders->random();
                $orderItem = $order->items->random();

                $invoicedQty = fake()->numberBetween(1, max(1, (int) $dcItem->remaining_invoice_quantity));
                $unitPrice = $dcItem->unit_price;
                $lineTotal = $invoicedQty * $unitPrice;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'delivery_challan_id' => $dc->id,
                    'delivery_challan_item_id' => $dcItem->id,
                    'order_booking_id' => $order->id,
                    'order_booking_item_id' => $orderItem->id,
                    'item_description' => $dcItem->item_description,
                    'unit' => $dcItem->unit,
                    'delivered_quantity' => $dcItem->delivered_quantity,
                    'invoiced_quantity' => $invoicedQty,
                    'remaining_invoice_quantity' => $dcItem->remaining_invoice_quantity - $invoicedQty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'remarks' => fake()->optional()->sentence(),
                ]);

                $dcItem->update([
                    'invoiced_quantity' => $dcItem->invoiced_quantity + $invoicedQty,
                    'remaining_invoice_quantity' => $dcItem->remaining_invoice_quantity - $invoicedQty,
                ]);

                $subtotal += $lineTotal;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
            ]);

            $invoices->push($invoice);
        }
        return $invoices;
    }

    private function seedPayments(Collection $customers, Collection $invoices): void
    {
        for ($i = 0; $i < $this->count; $i++) {
            $customerInvoices = $invoices->random(1);
            $invoice = $customerInvoices->first();
            $payAmount = fake()->randomFloat(2, 100, min(10000, (float) $invoice->grand_total));

            $payment = Payment::create([
                'payment_no' => 'BBPAY/' . str_pad((string) ($this->paymentOffset + $i + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
                'customer_id' => $invoice->customer_id,
                'payment_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer', 'UPI', 'Cheque', 'NEFT']),
                'reference_no' => fake()->optional(0.5)->numerify('REF-####'),
                'remarks' => fake()->optional()->sentence(),
                'total_amount' => $payAmount,
                'payment_status' => 'Paid',
                'created_by' => $this->admin->id,
            ]);

            PaymentItem::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'paid_amount' => $payAmount,
                'remarks' => fake()->optional()->sentence(),
            ]);

            $newPaid = $invoice->paid_amount + $payAmount;
            $status = $newPaid >= $invoice->grand_total ? 'paid' : 'partially_paid';
            $paymentStatus = $newPaid >= $invoice->grand_total ? 'Paid' : 'Partial';
            $invoice->update([
                'paid_amount' => $newPaid,
                'payment_status' => $paymentStatus,
                'status' => $status,
            ]);
        }
    }

    private function seedPurchases(Collection $suppliers, Collection $books): void
    {
        for ($i = 0; $i < $this->count; $i++) {
            $purchase = Purchase::create([
                'purchase_no' => 'PO-' . now()->format('Y') . '-' . str_pad((string) ($this->purchaseOffset + $i + 1), 6, '0', STR_PAD_LEFT),
                'purchase_type' => 'manual',
                'supplier_id' => $suppliers->random()->id,
                'invoice_no' => fake()->optional(0.7)->numerify('INV-#####'),
                'invoice_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'purchase_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'notes' => fake()->optional()->sentence(),
                'status' => fake()->randomElement(['draft', 'confirmed', 'confirmed']),
                'created_by' => $this->admin->id,
            ]);

            $itemCount = fake()->numberBetween(1, 4);
            for ($j = 0; $j < $itemCount; $j++) {
                $qty = fake()->numberBetween(5, 100);
                $price = fake()->randomFloat(2, 50, 2000);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'book_id' => $books->random()->id,
                    'ordered_quantity' => $qty,
                    'received_quantity' => $qty,
                    'purchase_price' => $price,
                    'total' => round($qty * $price, 2),
                    'remarks' => fake()->optional()->sentence(),
                ]);
            }
        }
    }

    private function seedExpenses(Collection $expenseCategories): void
    {
        for ($i = 0; $i < $this->count; $i++) {
            Expense::create([
                'expense_no' => 'BBEXP/' . str_pad((string) ($this->expenseOffset + $i + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
                'expense_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'category_id' => $expenseCategories->random()->id,
                'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer', 'UPI', 'Cheque']),
                'reference_no' => fake()->optional(0.5)->numerify('REF-####'),
                'amount' => fake()->randomFloat(2, 100, 50000),
                'vendor_name' => fake()->company(),
                'remarks' => fake()->optional()->sentence(),
                'created_by' => $this->admin->id,
            ]);
        }
    }

    private function seedStocks(Collection $books): void
    {
        foreach ($books as $book) {
            Stock::create([
                'book_id' => $book->id,
                'current_quantity' => fake()->numberBetween(0, 200),
            ]);
        }
    }

    private function seedInventoryTransactions(Collection $books): void
    {
        $types = [
            InventoryTransactionType::OPENING,
            InventoryTransactionType::PURCHASE,
            InventoryTransactionType::PURCHASE,
            InventoryTransactionType::SALE,
            InventoryTransactionType::SALE,
            InventoryTransactionType::ADJUSTMENT,
            InventoryTransactionType::DAMAGE,
            InventoryTransactionType::TRANSFER_IN,
            InventoryTransactionType::TRANSFER_OUT,
        ];

        $balance = [];
        for ($i = 0; $i < $this->count; $i++) {
            $book = $books->random();
            $type = $types[array_rand($types)];
            $qtyIn = 0;
            $qtyOut = 0;

            if (in_array($type, [
                InventoryTransactionType::OPENING,
                InventoryTransactionType::PURCHASE,
                InventoryTransactionType::SALE_RETURN,
                InventoryTransactionType::TRANSFER_IN,
            ])) {
                $qtyIn = fake()->numberBetween(1, 100);
            } else {
                $qtyOut = fake()->numberBetween(1, 50);
            }

            $balance[$book->id] = ($balance[$book->id] ?? 0) + $qtyIn - $qtyOut;

            InventoryTransaction::create([
                'transaction_no' => 'INV/' . str_pad((string) ($this->inventoryTransactionOffset + $i + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->format('y'),
                'transaction_type' => $type,
                'book_id' => $book->id,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'balance_after' => max(0, $balance[$book->id]),
                'transaction_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'remarks' => fake()->optional()->sentence(),
                'created_by' => $this->admin->id,
            ]);
        }
    }
}
