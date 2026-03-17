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
        // Start the query with active products
        $query = Product::where('is_active', true);

        // Filter: Keyword (SKU or Name) - UPDATED TO ILIKE FOR CASE-INSENSITIVE POSTGRES SEARCH
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('item_code', 'ilike', "%{$keyword}%")
                  ->orWhere('item_name', 'ilike', "%{$keyword}%");
            });
        }

        // Filter: Inclusive Price Range
        if ($request->filled('min_price')) {
            $query->where('bulk_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('bulk_price', '<=', $request->max_price);
        }

        // Sorting Logic: Price Low-High or High-Low
        if ($request->filled('sort')) {
            if ($request->sort === 'price_asc') {
                $query->orderBy('bulk_price', 'asc');
            } elseif ($request->sort === 'price_desc') {
                $query->orderBy('bulk_price', 'desc');
            }
        } else {
            // Default sort
            $query->orderBy('category_name', 'asc');
        }

        // Fetch the filtered products
        $products = $query->get();

        // Dynamically extract only the categories that exist in these exact search results
        $categories = $products->pluck('category_name')->unique()->filter()->sort()->values()->toArray();

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

        $filePath = $request->file('catalog_pdf')->storeAs('uploads', 'latest_catalog.pdf', 'local');
        $fullPath = Storage::disk('local')->path($filePath);
        $scriptPath = base_path('pdf_extractor/extract.py');

        // Execute Python using PYTHONPATH
        $pythonPath = "PYTHONPATH=/app/venv/lib/python3.11/site-packages";
        $command = "$pythonPath python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($fullPath) . " 2>&1";

        $output = shell_exec($command);
        $data = json_decode($output, true);

        if (!$data || isset($data['error'])) {
            $errorDetail = $data['error'] ?? "Python Error: " . ($output ?: 'No response.');
            return back()->withErrors(['catalog_pdf' => "Sync Failed: " . $errorDetail]);
        }

        $incomingSkus = [];

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
        $request->validate(['selected_products' => 'required|string']);

        $selectedIds = json_decode($request->selected_products, true);

        if (empty($selectedIds)) {
            return back()->withErrors(['export' => 'Please select at least one product.']);
        }

        $products = Product::whereIn('id', $selectedIds)->orderBy('category_name')->get();

        $showPrice = $request->input('show_price', 'yes') === 'yes';
        $markupPercentage = (float) $request->input('markup_percentage', 0);

        foreach ($products as $product) {
            if ($showPrice && $product->bulk_price) {
                $multiplier = 1 + ($markupPercentage / 100);
                $product->custom_price = $product->bulk_price * $multiplier;
            } else {
                $product->custom_price = null;
            }
        }

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])->loadView('pdf.catalog', compact('products', 'showPrice'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('IPDS_Custom_Catalog.pdf');
    }
}