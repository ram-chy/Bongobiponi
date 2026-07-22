<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $suppliers = [
            [
                'name' => 'Rajesh Kumar',
                'company_name' => 'Eastern Book Distributors',
                'phone' => '9830123456',
                'email' => 'rajesh@easternbooks.in',
                'gst_number' => '19AABCE1234F1Z5',
                'address' => '23 College Street, Kolkata - 700073',
                'remarks' => 'Primary supplier for academic books',
            ],
            [
                'name' => 'Priya Sharma',
                'company_name' => 'National Library Supplies',
                'phone' => '9820198765',
                'email' => 'priya@nationallibsupplies.com',
                'gst_number' => '27AABCN5678G1Z2',
                'address' => '45 Lamington Road, Mumbai - 400007',
                'remarks' => 'Good for competitive exam books',
            ],
            [
                'name' => 'Amit Patel',
                'company_name' => 'Gujarat Paperbacks Pvt Ltd',
                'phone' => '9925112233',
                'email' => 'amit@gujaratpaperbacks.in',
                'gst_number' => '24AABCG9012H1Z8',
                'address' => '78 Ashram Road, Ahmedabad - 380009',
            ],
            [
                'name' => 'Sunita Reddy',
                'company_name' => 'South India Book Traders',
                'phone' => '9840155566',
                'email' => 'sunita@sibooktraders.com',
                'gst_number' => '33AABCS3456J1Z1',
                'address' => '12 Anna Salai, Chennai - 600002',
                'remarks' => 'Regional language books specialist',
            ],
            [
                'name' => 'Mohammad Irfan',
                'company_name' => 'Delhi Book Warehouse',
                'phone' => '9811123456',
                'email' => 'irfan@delhibookwarehouse.in',
                'gst_number' => '07AABCD7890K1Z6',
                'address' => '90 Nai Sarak, Chandni Chowk, Delhi - 110006',
            ],
            [
                'name' => 'Kavitha Nair',
                'company_name' => 'Kerala Publications',
                'phone' => '9847123456',
                'email' => 'kavitha@keralapub.in',
                'gst_number' => '32AABCK2345L1Z3',
                'address' => '34 MG Road, Kochi - 682016',
                'remarks' => 'Children books and educational toys',
            ],
            [
                'name' => 'Vikram Singh',
                'company_name' => 'Rajasthan Stationers & Books',
                'phone' => '9829111222',
                'email' => 'vikram@rajasthanbooks.com',
                'gst_number' => '08AABCR6789M1Z4',
                'address' => '56 MI Road, Jaipur - 302001',
            ],
            [
                'name' => 'Ananya Das',
                'company_name' => 'Northeast Book Hub',
                'phone' => '9864123456',
                'email' => 'ananya@nebookhub.in',
                'gst_number' => '18AABCN0123N1Z7',
                'address' => '15 Zoo Road, Guwahati - 781001',
                'remarks' => 'Assamese and Bengali literature',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['phone' => $supplier['phone']],
                array_merge($supplier, [
                    'created_by' => $user?->id,
                    'status' => true,
                ])
            );
        }
    }
}
