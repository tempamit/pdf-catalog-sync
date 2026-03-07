<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductController extends Controller
{
    /**
     * Show the Desktop Upload Form
     */
    public function showUploadForm()
    {
        return view('upload');
    }

    /**
     * Mobile-First Search & Catalog View
     */
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

        // 2. Filter by Max Price
        if ($request->filled('max_price')) {
            $query->where('bulk_price', '<=', $request->max_price);
        }

        // Paginate for mobile performance
        $products = $query->orderBy('category_name')->paginate(15)->withQueryString();

        return view('catalog', compact('products'));
    }

    /**
     * Handle the uploaded PDF and Sync the Database
     */
    public function processUpload(Request $request)
    {
        // Validate the file
        $request->validate([
            'catalog_pdf' => 'required|mimes:pdf|max:20480', // Max 20MB
        ]);

       // Store it in the 'public' disk so it's guaranteed to be in a predictable path
       $filePath = $request->file('catalog_pdf')->storeAs('uploads', 'latest_catalog.pdf', 'local');

       // Use the Storage facade to get the absolute path regardless of folder nesting
       $fullPath = Storage::disk('local')->path($filePath);

        // Path to your Python script
        $scriptPath = base_path('pdf_extractor/extract.py');

        /**
         * THE PERMISSION FIX:
         * Instead of executing /app/venv/bin/python3 (which causes Permission Denied),
         * we use the system's 'python3' but tell it to look for libraries (pdfplumber, etc.)
         * inside your virtual environment's site-packages.
         */
        $pythonPath = "PYTHONPATH=/app/venv/lib/python3.11/site-packages";
       // Use the global python but point the library path to our venv
        $command = "PYTHONPATH=/app/venv/lib/python3.11/site-packages python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($fullPath) . " 2>&1";

        $output = shell_exec($command);

        // Attempt to decode the JSON output from Python
        $data = json_decode($output, true);

        // RULE 1: Validation & Error Capture
        if (!$data || isset($data['error'])) {
            $errorDetail = $data['error'] ?? "System Error: " . ($output ?: 'No response from Python engine.');
            return back()->withErrors(['catalog_pdf' => "Sync Failed: " . $errorDetail]);
        }

        $incomingSkus = [];

        // RULE 2: Insert New & Update Existing
        foreach ($data as $item) {
            if (empty($item['item_code'])) continue;

            $incomingSkus[] = $item['item_code'];

            Product::updateOrCreate(
                ['item_code' => $item['item_code']],
                [
                    'category_name'    => $item['category_name'],
                    'item_name'        => $item['item_name'],
                    'colors_available' => $item['colors_available'],
                    'image_link'       => $item['image_link'],
                    'detail_link'      => $item['detail_link'],
                    'sample_price'     => $item['sample_price'],
                    'bulk_price'       => $item['bulk_price'],
                    'comments'         => $item['comments'],
                    'is_active'        => true, // Ensure it's active if updated
                ]
            );
        }

        // RULE 3: Soft Delete (Archive) items not in the new PDF
        if (count($incomingSkus) > 0) {
            Product::whereNotIn('item_code', $incomingSkus)->update(['is_active' => false]);
        }

        return back()->with('success', 'Database synced successfully! Processed ' . count($incomingSkus) . ' items.');
    }

    /**
     * Generate and Download the Marked-up PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Product::where('is_active', true);

        // Apply same filters as the current search view
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

        $products = $query->get();

        $showPrice = $request->input('show_price', 'yes') === 'yes';
        $markup = (float) $request->input('markup_percentage', 0);

        // Apply markup logic
        foreach ($products as $product) {
            if ($showPrice && $product->bulk_price) {
                $multiplier = 1 + ($markup / 100);
                $product->custom_price = $product->bulk_price * $multiplier;
            } else {
                $product->custom_price = null;
            }
        }

        $pdf = Pdf::loadView('pdf.catalog', compact('products', 'showPrice'));

        return $pdf->download('IPDS_Catalog_Export.pdf');
    }
}