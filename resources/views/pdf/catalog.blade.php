<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Catalog</title>
    <style>
        body { font-family: sans-serif; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .product-table { width: 100%; border-collapse: collapse; }
        .product-cell { width: 33.33%; padding: 15px; border: 1px solid #e5e7eb; text-align: center; vertical-align: top; }
        .img-container { height: 140px; display: block; margin-bottom: 10px; }
        .img-container img { max-width: 100%; max-height: 140px; object-fit: contain; }
        .placeholder { height: 140px; background: #f9fafb; color: #9ca3af; line-height: 140px; font-size: 12px; margin-bottom: 10px; border: 1px dashed #d1d5db; }
        .sku { font-size: 10px; color: #6b7280; font-weight: bold; text-transform: uppercase; margin: 0; }
        .title { font-size: 13px; font-weight: bold; margin: 5px 0 10px; min-height: 30px; }
        .price { font-size: 16px; font-weight: bold; color: #1d4ed8; margin: 0; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Interactive Pixels Product Catalog</h1>
        <p style="margin: 0; font-size: 12px; color: #666;">Generated on {{ date('d M Y') }}</p>
    </div>

    <table class="product-table">
        <tr>
        @foreach($products as $index => $product)
            <td class="product-cell">
            @if($product->image_link && str_starts_with($product->image_link, 'http'))
                    <div class="img-container">
                        <img src="{{ $product->image_link }}" alt="Product Image">
                    </div>
                @else
                    <div class="placeholder">No Image</div>
                @endif

                <p class="sku">{{ $product->item_code }}</p>
                <div class="title">{{ $product->item_name }}</div>

                @if($showPrice && $product->custom_price)
                    <p class="price">Rs. {{ number_format($product->custom_price, 2) }}</p>
                @endif
            </td>

            {{-- Break the row every 3 items --}}
            @if(($index + 1) % 3 == 0)
                </tr><tr>
            @endif
        @endforeach
        </tr>
    </table>

</body>
</html>