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
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Categories</label>
                    <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1 bg-gray-50">
                        @foreach($categories as $cat)
                        <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-gray-100 p-1 rounded">
                            <input type="checkbox" name="categories[]" value="{{ $cat }}"
                                   {{ is_array(request('categories')) && in_array($cat, request('categories')) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600">
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
                <h3 class="text-sm font-bold text-red-600 mb-3 uppercase">Export Current List</h3>
                <form action="{{ route('catalog.export') }}" method="POST" target="_blank" class="space-y-3">
                    @csrf
                    <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                    <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                    <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                    @if(is_array(request('categories')))
                        @foreach(request('categories') as $cat)
                            <input type="hidden" name="categories[]" value="{{ $cat }}">
                        @endforeach
                    @endif

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

                    <button type="submit" class="w-full bg-red-600 text-white font-bold py-2 rounded-lg hover:bg-red-700 transition">
                        Download PDF
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($products as $product)
                <div class="bg-white rounded-lg shadow border overflow-hidden flex flex-col">
                    <div class="relative h-48 bg-white border-b flex items-center justify-center p-2">
                        @if($product->image_link)
                            <img src="{{ $product->image_link }}" alt="{{ $product->item_name }}" class="max-w-full max-h-full object-contain">
                        @else
                            <div class="text-gray-300 text-xs italic bg-gray-50 w-full h-full flex items-center justify-center rounded">Image Placeholder</div>
                        @endif
                    </div>
                    <div class="p-3 flex-1 flex flex-col">
                        <p class="text-[10px] text-gray-500 font-bold uppercase">{{ $product->category_name }} &bull; {{ $product->item_code }}</p>
                        <h3 class="font-bold text-sm text-gray-800 mt-1 line-clamp-2">{{ $product->item_name }}</h3>
                        <div class="mt-auto pt-3 flex items-center justify-between">
                            <span class="text-md font-black text-blue-700">Rs. {{ number_format($product->bulk_price) }}</span>
                            @if($product->detail_link)
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

            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </main>
    </div>
</div>

</body>
</html>