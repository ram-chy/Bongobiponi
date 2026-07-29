<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\DeliveryChallanController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('verify-otp', [PasswordResetController::class, 'verifyOtp']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
});

Route::middleware(['auth:api', 'token.version', 'throttle:api'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware('role.admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{id}', [UserController::class, 'show']);
        Route::put('users/{user}/role', [UserController::class, 'updateRole']);
    });

    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{id}/restore', [CustomerController::class, 'restore']);

    Route::apiResource('suppliers', SupplierController::class);
    Route::post('suppliers/{id}/restore', [SupplierController::class, 'restore']);

    Route::apiResource('publishers', PublisherController::class);
    Route::post('publishers/{id}/restore', [PublisherController::class, 'restore']);

    Route::apiResource('authors', AuthorController::class);
    Route::post('authors/{id}/restore', [AuthorController::class, 'restore']);

    Route::apiResource('categories', CategoryController::class);
    Route::post('categories/{id}/restore', [CategoryController::class, 'restore']);

    Route::apiResource('books', BookController::class);
    Route::post('books/{id}/restore', [BookController::class, 'restore']);
    Route::post('books/upload-cover', [BookController::class, 'uploadCover']);

    Route::apiResource('purchases', PurchaseController::class);
    Route::post('purchases/{id}/restore', [PurchaseController::class, 'restore']);
    Route::post('purchases/{purchase}/confirm', [PurchaseController::class, 'confirm']);
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);

    Route::get('inventory', [InventoryController::class, 'index']);
    Route::get('inventory/ledger/{bookId}', [InventoryController::class, 'ledger']);
    Route::get('inventory/{bookId}', [InventoryController::class, 'show']);
    Route::middleware('role.manager')->group(function () {
        Route::post('inventory/opening', [InventoryController::class, 'opening']);
        Route::post('inventory/adjustment', [InventoryController::class, 'adjustment']);
        Route::post('inventory/damage', [InventoryController::class, 'damage']);
    });

    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/restore', [OrderController::class, 'restore']);
    Route::get('orders/{order}/download-pdf', [OrderController::class, 'downloadPDF']);

    Route::apiResource('delivery-challans', DeliveryChallanController::class);
    Route::post('delivery-challans/{id}/restore', [DeliveryChallanController::class, 'restore']);
    Route::get('delivery-challans/{delivery_challan}/download-pdf', [DeliveryChallanController::class, 'downloadPDF']);
    Route::get('orders/{order}/remaining-items', [DeliveryChallanController::class, 'remainingItems']);

    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{id}/restore', [InvoiceController::class, 'restore']);
    Route::get('invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPDF']);
    Route::get('delivery-challans/{deliveryChallan}/invoiceable-items', [InvoiceController::class, 'invoiceableItems']);

    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{id}/restore', [PaymentController::class, 'restore']);
    Route::get('payments/{payment}/download-pdf', [PaymentController::class, 'downloadPDF']);
    Route::get('customers/{customerId}/due-invoices', [PaymentController::class, 'dueInvoices']);

    Route::apiResource('expense-categories', ExpenseCategoryController::class);
    Route::post('expense-categories/{id}/restore', [ExpenseCategoryController::class, 'restore']);

    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{id}/restore', [ExpenseController::class, 'restore']);
    Route::get('expenses/{expense}/download-attachment', [ExpenseController::class, 'downloadAttachment'])->name('expenses.download-attachment');
});
