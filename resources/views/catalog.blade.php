<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductController extends Controller
{
    /**
     * Show the Upload Form
     */
    public function showUploadForm()
    {
        return view('upload');
    }

    /**
     * Desktop-First Advanced Search & Dashboard
     */
    public function index(Request $request)
    {
        // 1. Get unique categories to populate the sidebar checkboxes
        $categories = Product::where('is_active', true)
            ->whereNotNull('category_name')
            ->distinct()
            ->pluck('category_name')
            ->sort()
            ->values()
            ->toArray();

        // 2. Start the query with active products
        $query = Product::where('is_active', true);

        // Filter: Keyword (SKU or Name)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('item_code', 'like', "%{$keyword}%")
                  ->orWhere('item_name', 'like', "%{$keyword}%");
            });
        }

        // Filter: Multiple Categories (Array)
        if ($request->filled('categories') && is_array($request->categories)) {
            $query->whereIn('category_name', $request->categories);
        }

        // Filter: Inclusive Price Range
        if ($request->filled('min_price')) {
            $query->where('bulk_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('bulk_price', '<=', $request->max_price);
        }

        // Paginate results for the dashboard
        $products = $query->orderBy('category_name')->paginate(15)->withQueryString();

        return view('catalog', compact('products', 'categories'));
    }

    /**
     * Handle the uploaded PDF and Sync the Database via Python
     */
    public function processUpload(Request $request)
    {
        // Validate the file (up to 50MB)
        $request->validate([
            'catalog_pdf' => 'required|mimes:pdf|max:51200',
        ]);

        // Store the file on the local disk to guarantee a predictable path
        $filePath = $request->file('catalog_pdf')->storeAs('uploads', 'latest_catalog.pdf', 'local');
        $fullPath = Storage::disk('local')->path($filePath);

        // Path to your Python script
        $scriptPath = base_path('pdf_extractor/extract.py');

        // Execute Python using PYTHONPATH to bypass strict directory permissions
        $pythonPath = "PYTHONPATH=/app/venv/lib/python3.11/site-packages";
        $command = "$pythonPath python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($fullPath) . " 2>&1";

        $output = shell_exec($command);
        $data = json_decode($output, true);

        // Validation & Raw Error Capture
        if (!$data || isset($data['error'])) {
            $errorDetail = $data['error'] ?? "Python Error: " . ($output ?: 'No response.');
            return back()->withErrors(['catalog_pdf' => "Sync Failed: " . $errorDetail]);
        }

        $incomingSkus = [];

        // Insert New & Update Existing
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
                    'is_active'        => true,
                ]
            );
        }

        // Smart Archiving: Hide products that were removed from the master PDF
        if (count($incomingSkus) > 0) {
            Product::whereNotIn('item_code', $incomingSkus)->update(['is_active' => false]);
        }

        return back()->with('success', 'Database synced successfully! Processed ' . count($incomingSkus) . ' active items.');
    }

    /**
     * Generate and Download the Custom PDF Catalog
     */
    public function exportPdf(Request $request)
    {
        $query = Product::where('is_active', true);

        // Mirror the exact same filters from the Advanced Search
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('item_code', 'like', "%{$keyword}%")
                  ->orWhere('item_name', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('categories') && is_array($request->categories)) {
            $query->whereIn('category_name', $request->categories);
        }

        if ($request->filled('min_price')) {
            $query->where('bulk_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('bulk_price', '<=', $request->max_price);
        }

        // Get ALL matching products for the export (no pagination)
        $products = $query->orderBy('category_name')->get();

        // Check if the user wants prices shown and grab the markup percentage
        $showPrice = $request->input('show_price', 'yes') === 'yes';
        $markupPercentage = (float) $request->input('markup_percentage', 0);

        // Apply dynamic pricing logic
        foreach ($products as $product) {
            if ($showPrice && $product->bulk_price) {
                $multiplier = 1 + ($markupPercentage / 100);
                $product->custom_price = $product->bulk_price * $multiplier;
            } else {
                $product->custom_price = null;
            }
        }

        // Load the view from resources/views/pdf/catalog.blade.php
        $pdf = Pdf::loadView('pdf.catalog', compact('products', 'showPrice'));

        // Optimize paper size for catalog printing
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('IPDS_Custom_Catalog.pdf');
    }
}