<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Desktop Upload Routes
Route::get('/upload', [ProductController::class, 'showUploadForm'])->name('upload.form');
Route::post('/upload', [ProductController::class, 'processUpload'])->name('upload.process');

// Mobile-First Search & Download Route (Placeholder for later)
Route::get('/', function () {
    return view('catalog'); // We will build this mobile UI later

    // Mobile Search Route
Route::get('/', [ProductController::class, 'index'])->name('catalog.index');

// PDF Export Route (We will build the logic for this next)
Route::post('/export-pdf', [ProductController::class, 'exportPdf'])->name('catalog.export');
});