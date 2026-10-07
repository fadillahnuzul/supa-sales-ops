<?php

use App\Http\Controllers\InquiryPrintController;
use App\Http\Controllers\PrintTemplateController;
use App\Http\Controllers\SalesSignatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Inquiry Print Menu
Route::prefix('inquiries/print')->group(function () {
    Route::get(
        '/',
        [InquiryPrintController::class, 'index']
    );
    Route::get(
        '/templates',
        [PrintTemplateController::class, 'index']
    );
    Route::post(
        '/templates',
        [PrintTemplateController::class, 'store']
    );
    Route::delete(
        '/templates/{printTemplate}',
        [PrintTemplateController::class, 'destroy']
    );
    Route::get(
        '/signatures',
        [SalesSignatureController::class, 'index']
    );
    Route::post(
        '/signatures',
        [SalesSignatureController::class, 'store']
    );
    Route::get(
        '/{id}/excel',
        [InquiryPrintController::class, 'exportExcel']
    )->whereNumber('id');

    Route::post(
        '/{id}/word',
        [InquiryPrintController::class, 'exportWord']
    )->whereNumber('id');
    Route::post(
        '/{id}/pdf',
        [InquiryPrintController::class, 'exportPdf']
    )->whereNumber('id');
    Route::get(
        '/{id}',
        [InquiryPrintController::class, 'show']
    )->whereNumber('id');
});
