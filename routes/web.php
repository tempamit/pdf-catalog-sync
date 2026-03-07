<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// Desktop Upload Routes
Route::get('/upload', [ProductController::class, 'showUploadForm'])->name('upload.form');
Route::post('/upload', [ProductController::class, 'processUpload'])->name('upload.process');

// Mobile-First Search & Download Route (Placeholder for later)
Route::get('/', function () {
    return view('catalog'); // We will build this mobile UI later
});