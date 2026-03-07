<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // 1. Show the Desktop Upload Form
    public function showUploadForm()
    {
        return view('upload');
    }

    // 2. Handle the uploaded PDF and trigger Python
    public function processUpload(Request $request)
    {
        $request->validate([
            'catalog_pdf' => 'required|mimes:pdf|max:20480', // Max 20MB
        ]);

        // Save the file to the local storage temporarily
        $path = $request->file('catalog_pdf')->storeAs('uploads', 'latest_catalog.pdf');

        // NOTE: This is where we will trigger the Python script in the next step!
        // $command = escapeshellcmd("python3 ../pdf_extractor/extract.py storage/app/uploads/latest_catalog.pdf");
        // $output = shell_exec($command);

        return back()->with('success', 'PDF uploaded successfully! Processing started in the background.');
    }
}