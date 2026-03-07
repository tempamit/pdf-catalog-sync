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
                  ->orWhere('item_name', 'like', "%{$keyword}%")
                  ->orWhere('category_name', 'like', "%{$keyword}%");
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

        // THE MAGIC LINE: Pass BOTH products and categories to the view!
        return view('catalog', compact('products', 'categories'));
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
    // 1. Start the query with only active products
    $query = Product::where('is_active', 1);

    // 2. Filter by Product Code (Exact match)
    if ($request->filled('product_code')) {
        // Adjust 'code' to your actual database column name if it's different
        $query->where('code', $request->product_code);
    }

    // 3. Filter by Keyword (Searches in name OR description)
    if ($request->filled('keyword')) {
        $keyword = $request->keyword;
        $query->where(function($q) use ($keyword) {
            // Using 'ilike' for case-insensitive search in PostgreSQL
            $q->where('name', 'ilike', "%{$keyword}%")
              ->orWhere('description', 'ilike', "%{$keyword}%");
        });
    }

    // 4. Filter by Price Range (Inclusive: e.g., 100 to 300)
    if ($request->filled('min_price') && $request->filled('max_price')) {
        $query->whereBetween('price', [$request->min_price, $request->max_price]);
    } elseif ($request->filled('min_price')) {
        $query->where('price', '>=', $request->min_price);
    } elseif ($request->filled('max_price')) {
        $query->where('price', '<=', $request->max_price);
    }

    // 5. Filter by Multiple Categories (Only grabs products in selected categories)
    if ($request->filled('categories') && is_array($request->categories)) {
        // Adjust 'category_id' to your actual column name
        $query->whereIn('category_id', $request->categories);
    }

    // 6. Execute the query to get the filtered products
    $products = $query->get();

    // 7. Handle any markup or custom pricing logic you previously had
    $showPrice = $request->input('show_price', 'yes');
    foreach ($products as $product) {
        // Keeping your previous logic intact just in case
        $product->custom_price = null;
    }

    // 8. Generate and download the PDF
    // Give DOMPDF permission to download external images
    $pdf = Pdf::setOptions(['isRemoteEnabled' => true])->loadView('pdf.catalog', compact('products', 'showPrice'));
    return $pdf->download('IPDS_Catalog_Export.pdf');
}
}