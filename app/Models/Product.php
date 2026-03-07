<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Define which fields can be mass-assigned during our PDF sync
    protected $fillable = [
        'category_name',
        'item_code',
        'item_name',
        'colors_available',
        'image_link',
        'detail_link',
        'sample_price',
        'bulk_price',
        'comments',
        'is_active',
    ];

    // Cast the prices to floats and the active flag to a boolean for easier math and logic later
    protected $casts = [
        'sample_price' => 'float',
        'bulk_price' => 'float',
        'is_active' => 'boolean',
    ];
}