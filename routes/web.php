<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

//Database Center
Route::get(
    '/database-center',
    [CustomerController::class, 'index']
)->name('database-center');
//Customer Database
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
Route::get('/inquiry', function () {
    return Inertia::render('Inquiry');
})->name('inquiry');
// Route::middleware('auth')->group(function () {
//     Route::get('/dashboard', function () {
//         return Inertia::render('Dashboard');
//     })->name('dashboard');
// });
