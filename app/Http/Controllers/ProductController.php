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

    // Mobile-First Search & Catalog View
    public function index(Request $request)
    {
        // Start with only active products
        $query = Product::where('is_active', true);

        // 1. Search by Category, SKU, or Keyword
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('item_code', 'like', "%{$keyword}%")
                  ->orWhere('item_name', 'like', "%{$keyword}%")
                  ->orWhere('category_name', 'like', "%{$keyword}%");
            });
        }

        // 2. Filter by Max Price (using the Bulk Price as the baseline)
        if ($request->filled('max_price')) {
            $query->where('bulk_price', '<=', $request->max_price);
        }

        // Paginate so your phone doesn't crash loading 1000 items at once
        $products = $query->paginate(15)->withQueryString();

        return view('catalog', compact('products'));
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

    // Generate and Download the PDF
    public function exportPdf(Request $request)
    {
        // 1. Recreate the exact same search query
        $query = Product::where('is_active', true);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('item_code', 'like', "%{$keyword}%")
                  ->orWhere('item_name', 'like', "%{$keyword}%")
                  ->orWhere('category_name', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('max_price')) {
            $query->where('bulk_price', '<=', $request->max_price);
        }

        // Get all matching products for the export (no pagination here)
        $products = $query->get();

        // 2. Grab the export settings from your modal
        $showPrice = $request->input('show_price', 'yes') === 'yes';
        $markupPercentage = (float) $request->input('markup_percentage', 0);

        // 3. Calculate the new custom prices
        foreach ($products as $product) {
            if ($showPrice && $product->bulk_price) {
                // Example: 150 base price + 50% markup = 150 * 1.50 = 225
                $multiplier = 1 + ($markupPercentage / 100);
                $product->custom_price = $product->bulk_price * $multiplier;
            } else {
                $product->custom_price = null;
            }
        }

        // 4. Generate the PDF using a specific view
        $pdf = \PDF::loadView('pdf.catalog', compact('products', 'showPrice'));

        // Download the file instantly to your device
        return $pdf->download('IPDS_Custom_Catalog.pdf');
    }
}