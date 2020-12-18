<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function products()
    {
        $products = Product::all();

        return response()->json($products, 200);
    }
    public function productById($slug)
    {
        $product  = Product::where('slug', '=', $slug)->firstOrFail();

        return response()->json($product, 200);
    }
}
