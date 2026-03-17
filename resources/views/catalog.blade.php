<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>UniGifts Product Catalogue</title>
    <style>
        /* This margin gives space for the fixed header and footer on every page */
        @page {
            margin: 90px 25px 70px 25px;
        }
        body { font-family: sans-serif; color: #333; margin: 0; padding: 0; }

        /* Fixed Header appearing on every page */
        header {
            position: fixed;
            top: -65px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Fixed Footer appearing on every page */
        footer {
            position: fixed;
            bottom: -50px;
            left: 0px;
            right: 0px;
            height: 40px;
            text-align: center;
            font-size: 10px;
            color: #4b5563;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            line-height: 1.4;
        }

        .header-title { font-size: 16px; font-weight: bold; margin: 0; color: #1f2937; text-transform: uppercase; letter-spacing: 1px; }
        .header-subtitle { font-size: 11px; font-weight: bold; color: #6b7280; margin-top: 4px; }

        .footer-contact { font-weight: bold; color: #1f2937; }
        .footer-disclaimer { font-style: italic; color: #b91c1c; font-weight: bold; margin-top: 2px;}

        .product-table { width: 100%; border-collapse: collapse; }
        .product-cell { width: 33.33%; padding: 12px; border: 1px solid #e5e7eb; text-align: center; vertical-align: top; }

        .img-container { height: 130px; display: block; margin-bottom: 8px; }
        .img-container img { max-width: 100%; max-height: 130px; object-fit: contain; }
        .placeholder { height: 130px; background: #f9fafb; color: #9ca3af; line-height: 130px; font-size: 12px; margin-bottom: 8px; border: 1px dashed #d1d5db; }

        .sku { font-size: 9px; color: #6b7280; font-weight: bold; text-transform: uppercase; margin: 0; }
        .title { font-size: 11px; font-weight: bold; margin: 4px 0 8px; min-height: 28px; line-height: 1.3; }
        .price { font-size: 14px; font-weight: bold; color: #1d4ed8; margin: 0; }
    </style>
</head>
<body>

    <header>
        <h1 class="header-title">UniGifts by Interactive Pixels Product Catalogue</h1>
        <div class="header-subtitle">Version {{ $versionCode }} generated on {{ date('d M Y') }}</div>
    </header>

    <footer>
        <div class="footer-contact">
            www.unigifts.in &nbsp;|&nbsp; info@unigifts.in &nbsp;|&nbsp; +91-7503010601 &nbsp;|&nbsp; instagram.com/unigifts.in
        </div>
        <div class="footer-disclaimer">
            * GST + Branding + Freight charges extra as applicable
        </div>
    </footer>

    <main>
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
                        <p class="price">Rs. {{ number_format($product->custom_price) }}</p>
                    @endif
                </td>

                {{-- Break the row every 3 items --}}
                @if(($index + 1) % 3 == 0 && !$loop->last)
                    </tr><tr>
                @endif
            @endforeach
            </tr>
        </table>
    </main>

</body>
</html>