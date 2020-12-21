<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function products(Request $request)
    {
        $pageSize = 10;

        $page = (int)$request->query('pageNumber', 1);
        $keyword = '%' . $request->query('keyword', '') . '%';

        $count =  DB::table('products')
            ->where('name', 'like', $keyword)
            ->count();

        $products = Product::where('name', 'like', $keyword)
            ->offset($pageSize * ($page - 1))
            ->limit($pageSize)
            ->get();

        return response()->json([
            'products' => $products,
            'page' => $page,
            'pages' => round($count / $pageSize)
        ], 200);
    }
    public function productById($slug)
    {
        $product  = Product::where('slug', '=', $slug)->firstOrFail();

        return response()->json($product, 200);
    }
}
