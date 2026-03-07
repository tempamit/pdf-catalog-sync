<div class="search-dashboard-container" style="max-width: 900px; margin: 0 auto; padding: 20px;">
    <h2>Advanced Product Search & Export</h2>

    <form action="{{ route('catalog.export') }}" method="POST" class="advanced-search-form">
        @csrf

        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <label><strong>Product Code</strong></label>
                <input type="text" name="product_code" placeholder="e.g. PRD-123" style="width: 100%; padding: 8px;">
            </div>
            <div style="flex: 1;">
                <label><strong>Keyword</strong></label>
                <input type="text" name="keyword" placeholder="Search name or description..." style="width: 100%; padding: 8px;">
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <label><strong>Minimum Price (Rs)</strong></label>
                <input type="number" name="min_price" value="100" style="width: 100%; padding: 8px;">
            </div>
            <div style="flex: 1;">
                <label><strong>Maximum Price (Rs)</strong></label>
                <input type="number" name="max_price" value="300" style="width: 100%; padding: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label><strong>Select Categories</strong></label>
            <div style="display: flex; gap: 15px; margin-top: 8px;">
                <label><input type="checkbox" name="categories[]" value="A"> Category A</label>
                <label><input type="checkbox" name="categories[]" value="B"> Category B</label>
                <label><input type="checkbox" name="categories[]" value="C"> Category C</label>
                <label><input type="checkbox" name="categories[]" value="D"> Category D</label>
            </div>
        </div>

        <div>
            <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer;">
                Filter & Export PDF
            </button>
        </div>
    </form>
</div>