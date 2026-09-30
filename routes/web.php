<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');
Route::get('/database-center', function () {
    return Inertia::render('DatabaseCenter');
})->name('database-center');
Route::get('/inquiry', function () {
    return Inertia::render('Inquiry');
})->name('inquiry');
// Route::middleware('auth')->group(function () {
//     Route::get('/dashboard', function () {
//         return Inertia::render('Dashboard');
//     })->name('dashboard');
// });
