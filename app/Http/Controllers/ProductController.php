<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductController extends Controller
{
    public function showUploadForm()
    {
        return view('upload');
    }

    public function index(Request $request)
    {
        // 1. Get unique categories
        $categories = Product::where('is_active', true)
            ->whereNotNull('category_name')
            ->distinct()
            ->pluck('category_name')
            ->sort()
            ->values()
            ->toArray();

        $query = Product::where('is_active', true);

        // Filter: Keyword (SKU or Name)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('item_code', 'like', "%{$keyword}%")
                  ->orWhere('item_name', 'like', "%{$keyword}%");
            });
        }

        // Filter: Multiple Categories
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

        // NO PAGINATION: Get all matching results for easy selection
        $products = $query->orderBy('category_name')->get();

        return view('catalog', compact('products', 'categories'));
    }

    public function processUpload(Request $request)
    {
        $request->validate([
            'catalog_pdf' => 'required|mimes:pdf|max:51200',
        ]);

        $filePath = $request->file('catalog_pdf')->storeAs('uploads', 'latest_catalog.pdf', 'local');
        $fullPath = Storage::disk('local')->path($filePath);
        $scriptPath = base_path('pdf_extractor/extract.py');

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

    public function exportPdf(Request $request)
    {
        // Require at least one product to be selected
        $request->validate(['selected_products' => 'required|string']);

        // Decode the JSON array of selected product IDs sent from the frontend
        $selectedIds = json_decode($request->selected_products, true);

        if (empty($selectedIds)) {
            return back()->withErrors(['export' => 'Please select at least one product.']);
        }

        // Fetch ONLY the specific products the user checked
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

        // Allow DOMPDF to download remote images safely
        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])->loadView('pdf.catalog', compact('products', 'showPrice'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('IPDS_Custom_Catalog.pdf');
    }
}