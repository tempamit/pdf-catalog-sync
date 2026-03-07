<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPDS Product Catalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-6">

        <aside class="w-full md:w-1/4 bg-white p-6 rounded-xl shadow-md h-fit md:sticky top-4 border">

            <h2 class="text-xl font-bold mb-4 border-b pb-2">Advanced Filters</h2>
            <form action="{{ route('catalog.index') }}" method="GET" class="space-y-4">

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Keyword / SKU</label>
                    <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Enter code or name..."
                           class="w-full mt-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Price Range (Rs.)</label>
                    <div class="flex gap-2 mt-1">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                               class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                               class="w-1/2 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-semibold text-gray-600">Categories</label>
                        <label class="text-xs text-blue-600 font-bold cursor-pointer hover:underline">
                            <input type="checkbox" id="selectAllCategories" class="mr-1">Select All
                        </label>
                    </div>
                    <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1 bg-gray-50">
                        @foreach($categories as $cat)
                        <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-gray-100 p-1 rounded">
                            <input type="checkbox" name="categories[]" value="{{ $cat }}"
                                   {{ is_array(request('categories')) && in_array($cat, request('categories')) ? 'checked' : '' }}
                                   class="cat-checkbox rounded border-gray-300 text-blue-600">
                            <span>{{ $cat }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 flex gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 text-white font-bold py-2 rounded-lg hover:bg-black transition">Search</button>
                    <a href="{{ route('catalog.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-gray-600 hover:bg-gray-300 text-center font-bold">Clear</a>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-bold text-red-600 mb-1 uppercase">Export Selected</h3>
                <p class="text-xs text-gray-500 mb-4"><span id="exportCount" class="font-bold text-lg text-gray-800">0</span> items selected</p>

                @if($errors->has('export'))
                    <div class="text-red-500 text-xs font-bold mb-2">{{ $errors->first('export') }}</div>
                @endif

                <form action="{{ route('catalog.export') }}" method="POST" target="_blank" class="space-y-3" id="exportForm">
                    @csrf
                    <input type="hidden" name="selected_products" id="selectedProductsInput" value="[]">

                    <div class="flex items-center justify-between gap-2">
                        <label class="text-sm font-semibold text-gray-600">Show Prices?</label>
                        <select name="show_price" class="border border-gray-300 rounded px-2 py-1 text-sm">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <label class="text-sm font-semibold text-gray-600">Markup %</label>
                        <input type="number" name="markup_percentage" value="0" min="0" class="border border-gray-300 rounded px-2 py-1 text-sm w-20 text-center">
                    </div>

                    <button type="button" onclick="selectAllProducts()" class="w-full bg-blue-100 text-blue-700 font-bold py-2 rounded-lg hover:bg-blue-200 transition text-sm mb-2">
                        Select All Visible Products
                    </button>

                    <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition">
                        Generate Custom PDF
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="productGrid">
                @forelse($products as $product)
                <div class="product-card bg-white rounded-lg shadow border overflow-hidden flex flex-col relative transition" id="card-{{ $product->id }}">

                    <input type="checkbox" class="product-selector absolute top-2 left-2 z-10 w-5 h-5 cursor-pointer" data-id="{{ $product->id }}">

                    <div class="relative h-48 bg-white border-b flex items-center justify-center p-2">
                        @if($product->image_link && str_starts_with($product->image_link, 'http'))
                            <img src="{{ $product->image_link }}" alt="{{ $product->item_name }}" loading="lazy" class="max-w-full max-h-full object-contain">
                        @else
                            <div class="text-gray-300 text-xs italic bg-gray-50 w-full h-full flex items-center justify-center rounded">No Image</div>
                        @endif
                    </div>

                    <div class="p-3 flex-1 flex flex-col">
                        <p class="text-[10px] text-gray-500 font-bold uppercase">{{ $product->category_name }} &bull; {{ $product->item_code }}</p>
                        <h3 class="font-bold text-sm text-gray-800 mt-1 line-clamp-2">{{ $product->item_name }}</h3>
                        <div class="mt-auto pt-3 flex items-center justify-between">
                            <span class="text-md font-black text-blue-700">Rs. {{ number_format($product->bulk_price) }}</span>
                            @if($product->detail_link && str_starts_with($product->detail_link, 'http'))
                                <a href="{{ $product->detail_link }}" target="_blank" class="text-xs text-blue-500 hover:underline">View</a>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-20 text-center text-gray-500 font-bold text-lg">
                    No products found matching your criteria.
                </div>
                @endforelse
            </div>
        </main>
    </div>
</div>

<script>
    // 1. Toggle all categories
    document.getElementById('selectAllCategories').addEventListener('change', function(e) {
        document.querySelectorAll('.cat-checkbox').forEach(cb => cb.checked = e.target.checked);
    });

    // 2. Manage Product Selection Array for PDF Export
    const selectedProducts = new Set();
    const countDisplay = document.getElementById('exportCount');
    const hiddenInput = document.getElementById('selectedProductsInput');

    function updateExportForm() {
        hiddenInput.value = JSON.stringify([...selectedProducts]);
        countDisplay.innerText = selectedProducts.size;
    }

    // Listen to individual product checkboxes
    document.querySelectorAll('.product-selector').forEach(cb => {
        cb.addEventListener('change', function() {
            const card = document.getElementById('card-' + this.dataset.id);
            if (this.checked) {
                selectedProducts.add(this.dataset.id);
                card.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50');
            } else {
                selectedProducts.delete(this.dataset.id);
                card.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50');
            }
            updateExportForm();
        });
    });

    // Helper to select all visible products at once
    function selectAllProducts() {
        document.querySelectorAll('.product-selector').forEach(cb => {
            if (!cb.checked) {
                cb.checked = true;
                // trigger change event to update arrays and styles
                cb.dispatchEvent(new Event('change'));
            }
        });
    }
// Helper to select all visible products at once// Helper to select all visible products at once


</script>

</body>
</html>