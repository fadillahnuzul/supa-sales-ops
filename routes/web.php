<?php

use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InquiryDetailController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// Database Center
Route::get(
    '/database-center',
    [CustomerController::class, 'index']
)->name('database-center');
// Customer Database
Route::post(
    '/database-center/customers',
    [CustomerController::class, 'store']
)->name('customers.store');
Route::post(
    '/database-center/customers/import',
    [CustomerController::class, 'import']
)->name('customers.import');
Route::get(
    '/database-center/customers/template',
    [CustomerController::class, 'template']
)->name('customers.template');
Route::put(
    '/database-center/customers/{customer}',
    [CustomerController::class, 'update']
)->name('customers.update');
Route::delete(
    '/database-center/customers/{customer}',
    [CustomerController::class, 'destroy']
)->name('customers.destroy');
// Product Database
Route::post(
    '/products/import',
    [ProductController::class, 'import']
)->name('products.import');
Route::get(
    '/products/template',
    [ProductController::class, 'template']
)->name('products.template');
Route::post(
    '/products',
    [ProductController::class, 'store']
)->name('products.store');

Route::put(
    '/products/{product}',
    [ProductController::class, 'update']
)->name('products.update');

Route::delete(
    '/products/{product}',
    [ProductController::class, 'destroy']
)->name('products.destroy');
// Competitor Product Database
Route::prefix('competitors')
    ->name('competitors.')
    ->group(function () {
        Route::post(
            '/import',
            [CompetitorController::class, 'import']
        )->name('import');
        Route::get(
            '/template',
            [CompetitorController::class, 'template']
        )->name('template');
        Route::post(
            '/',
            [CompetitorController::class, 'store']
        )->name('store');
        Route::put(
            '/products/{competitorProduct}',
            [CompetitorController::class, 'update']
        )->name('update');
        Route::delete(
            '/products/{competitorProduct}',
            [CompetitorController::class, 'destroyProduct']
        )->name('products.destroy');
        Route::delete(
            '/{competitor}',
            [CompetitorController::class, 'destroy']
        )->name('destroy');
    });

// Inquiry Menu
Route::get(
    '/inquiry',
    [InquiryController::class, 'index']
)->name('inquiry');
Route::post(
    '/inquiry',
    [InquiryController::class, 'store']
)->name('inquiry.store');
Route::put(
    '/inquiry/{inquiry}',
    [InquiryController::class, 'update']
)->name('inquiry.update');
Route::delete(
    '/inquiry/{inquiry}',
    [InquiryController::class, 'destroy']
)->name('inquiry.destroy');
Route::post(
    '/inquiry/{inquiry}/detail',
    [InquiryDetailController::class, 'store']
)->name('inquiry-detail.store');
Route::put(
    '/inquiry/detail/{detail}',
    [InquiryDetailController::class, 'update']
)->name('inquiry-detail.update');
Route::delete(
    '/inquiry/detail/{detail}',
    [InquiryDetailController::class, 'destroy']
)->name('inquiry-detail.destroy');

// Menu Print
Route::get('/inquiry-print', function () {
    return Inertia::render('InquiryPrintCenter');
})->name('inquiry-print');

// Route::middleware('auth')->group(function () {
//     Route::get('/dashboard', function () {
//         return Inertia::render('Dashboard');
//     })->name('dashboard');
// });
