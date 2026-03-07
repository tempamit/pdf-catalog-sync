<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Desktop Upload Routes
Route::get('/upload', [ProductController::class, 'showUploadForm'])->name('upload.form');
Route::post('/upload', [ProductController::class, 'processUpload'])->name('upload.process');

// Mobile Search Route (This is what was missing!)
Route::get('/', [ProductController::class, 'index'])->name('catalog.index');

// PDF Export Route
Route::post('/export-pdf', [ProductController::class, 'exportPdf'])->name('catalog.export');