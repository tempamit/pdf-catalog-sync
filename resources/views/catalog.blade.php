<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <div class="max-w-md mx-auto bg-white min-h-screen shadow-md relative pb-24 text-center">

        <header class="bg-blue-600 text-white p-4 sticky top-0 z-10">
            <h1 class="text-xl font-bold">Product Catalog</h1>
        </header>

        <div class="p-4 border-b">
            <form action="{{ route('catalog.index') }}" method="GET" class="space-y-3">
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Search SKU, Name, or Category..."
                    class="w-full border rounded-full px-4 py-2 text-center focus:outline-none focus:ring-2 focus:ring-blue-500">

                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max Bulk Price (Rs)"
                    class="w-full border rounded-full px-4 py-2 text-center focus:outline-none focus:ring-2 focus:ring-blue-500">

                <button type="submit" class="w-full bg-gray-800 text-white font-bold py-2 rounded-full hover:bg-gray-900 transition">
                    Search Products
                </button>
            </form>
        </div>

        <div class="p-4 space-y-4">
            @forelse($products as $product)
                <div class="border rounded-xl p-4 shadow-sm flex flex-col items-center">
                    <div class="w-24 h-24 bg-gray-200 rounded-md mb-3 flex items-center justify-center text-xs text-gray-500">
                        Image: {{ $product->item_code }}
                    </div>
                    <span class="text-xs font-semibold text-blue-600 mb-1">{{ $product->category_name }}</span>
                    <h2 class="font-bold text-lg leading-tight mb-1">{{ $product->item_name }}</h2>
                    <p class="text-sm text-gray-500 mb-2">Code: <span class="font-bold text-gray-700">{{ $product->item_code }}</span></p>
                    <p class="font-bold text-green-600 text-lg">Rs. {{ number_format($product->bulk_price, 2) }}</p>
                </div>
            @empty
                <p class="text-gray-500 py-10">No products found.</p>
            @endforelse

            <div class="mt-4">
                {{ $products->links() }} </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 max-w-md mx-auto bg-white border-t">
            <button onclick="openExportModal()" class="w-full bg-red-600 text-white font-bold py-3 rounded-full shadow-lg hover:bg-red-700 transition">
                Export Current List to PDF
            </button>
        </div>

    </div>

    <div id="exportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm text-center">
            <h3 class="text-xl font-bold mb-4">Export PDF Settings</h3>

            <form action="{{ route('catalog.export') }}" method="POST">
                @csrf
                <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                <input type="hidden" name="max_price" value="{{ request('max_price') }}">

                <div class="mb-4">
                    <label class="block font-semibold mb-2 text-gray-700">Display Prices?</label>
                    <select id="priceToggle" name="show_price" onchange="toggleMarkup()" class="w-full border rounded-lg px-4 py-2 text-center text-lg">
                        <option value="yes">Export WITH Price</option>
                        <option value="no">Export WITHOUT Price</option>
                    </select>
                </div>

                <div id="markupSection" class="mb-6">
                    <label class="block font-semibold mb-2 text-gray-700">Price Markup (%)</label>
                    <p class="text-xs text-gray-500 mb-2">Increase base bulk price by 0% to 500%</p>
                    <input type="range" name="markup_percentage" id="markupRange" min="0" max="500" value="0"
                        oninput="document.getElementById('markupValue').innerText = this.value + '%'"
                        class="w-full mb-2">
                    <div class="text-2xl font-bold text-blue-600" id="markupValue">0%</div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeExportModal()" class="w-1/2 bg-gray-200 text-gray-800 font-bold py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="w-1/2 bg-blue-600 text-white font-bold py-2 rounded-lg shadow-md">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openExportModal() { document.getElementById('exportModal').classList.remove('hidden'); }
        function closeExportModal() { document.getElementById('exportModal').classList.add('hidden'); }
        function toggleMarkup() {
            const toggle = document.getElementById('priceToggle').value;
            const markupSection = document.getElementById('markupSection');
            markupSection.style.display = (toggle === 'yes') ? 'block' : 'none';
        }
    </script>
</body>
</html>