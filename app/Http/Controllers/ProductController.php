<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // Show the Desktop Upload Form
    public function showUploadForm()
    {
        return view('upload');
    }

    // Handle the uploaded PDF and Sync the Database
    public function processUpload(Request $request)
    {
        // Validate the file
        $request->validate([
            'catalog_pdf' => 'required|mimes:pdf|max:20480', // Max 20MB
        ]);

        // Save the file temporarily
        $filePath = $request->file('catalog_pdf')->storeAs('uploads', 'latest_catalog.pdf');
        $fullPath = storage_path('app/' . $filePath);

        // Define the path to our Python script
        $scriptPath = base_path('pdf_extractor/extract.py');

        // Execute the Python script and capture the JSON output
        // Note: Make sure 'python3' is accessible in your Hostinger/Coolify environment
        $command = escapeshellcmd("python3 {$scriptPath} \"{$fullPath}\"");
        $output = shell_exec($command);

        // Decode the JSON string returned from Python
        $data = json_decode($output, true);

        // RULE 1: Strict Format Validation
        if (!$data || isset($data['error'])) {
            $errorMessage = $data['error'] ?? 'Failed to parse the PDF. Please ensure the table format strictly matches the master template.';
            return back()->withErrors(['catalog_pdf' => $errorMessage]);
        }

        $incomingSkus = [];

        // RULE 2: Insert New & Update Existing
        foreach ($data as $item) {
            // Skip rows that somehow missed an item code
            if (empty($item['item_code'])) continue;

            $incomingSkus[] = $item['item_code'];

            // updateOrCreate searches by the first array (the anchor),
            // and updates/inserts using the second array (the data).
            Product::updateOrCreate(
                ['item_code' => $item['item_code']],
                [
                    'category_name' => $item['category_name'],
                    'item_name' => $item['item_name'],
                    'colors_available' => $item['colors_available'],
                    'image_link' => $item['image_link'],
                    'detail_link' => $item['detail_link'],
                    'sample_price' => $item['sample_price'],
                    'bulk_price' => $item['bulk_price'],
                    'comments' => $item['comments'],
                    'is_active' => true, // Reactivate if it was previously soft-deleted
                ]
            );
        }

        // RULE 3: Smart Archiving (Soft Deletes)
        // Find all products in the database whose SKU is NOT in the new PDF, and hide them
        if (count($incomingSkus) > 0) {
            Product::whereNotIn('item_code', $incomingSkus)->update(['is_active' => false]);
        }

        return back()->with('success', 'Catalog synced successfully! Processed ' . count($incomingSkus) . ' active items.');
    }
}