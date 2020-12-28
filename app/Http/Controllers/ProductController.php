<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function products(Request $request)
    {
        $pageSize = 10;

        $page = (int)$request->query('pageNumber', 1);
        $keyword = '%' . $request->query('keyword', '') . '%';

        $count = Product::where('name', 'like', $keyword)->count();

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
    public function productBySlug($slug)
    {
        $product  = Product::with('reviews')
            ->where('slug', '=', $slug)->firstOrFail();

        return response()->json($product, 200);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json(['message' => 'Product deleted'], 204);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }

    /**
     * Create new Product
     *
     * @return json
     */
    public function store()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $productName = 'Sample name';
            $slug = Str::slug($productName, '-');
            $next = 2;

            // Loop until we can query for the slug and it returns false
            while (Product::where('slug', '=', $slug)->first()) {
                $slug = $slug . '-' . $next;
                $next++;
            }
            $product = Product::create([
                'slug' => $slug,
                'user_id' => Auth::user()->id,
                'category_id' => 1,
                'sub_category_id' => 1,
                'name' => 'Sample name',
                'description' => 'Sample description',
                'price' => 0,
                'image' => '/images/sample.jpg',
                'brand' => 'Apple',
                'count_stock' => 0,
                'rating' => 0,
                'num_reviews' => 0,
            ]);
            return response()->json($product, 201);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }

    /**
     * Update the product resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'image' => 'required',
            'brand' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $product = Product::findOrFail($id);

        if ($product && Auth::user()->is_admin) {

            $product->update($validator->validated());
            return response()->json($product, 200);
        }
        return response()->json(['message' => 'Something went wrong'], 400);
    }
    /**
     * Upload Image to storage
     *
     * @param Request $request
     * @return Json
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|mimes:png,jpeg,jpg|max:2048',
        ]);

        $path = $request->file('image')->store('uploads/product');
        return response()->json($path, 200);
    }

    /**
     * Add product Review
     *
     * @param Request $request
     * @param inst $id
     * @return JSON
     */
    public function addProductReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required',
            'comment' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $product = Product::findOrFail($id);

        $user = auth()->user();
        $user_id = $user->id;

        $checkProductReview = ProductReview::where('user_id', $user_id)
            ->where('product_id', $product->id)->get();

        if (!$checkProductReview->isEmpty()) {
            return response()->json(['message' => 'Product already reviewed'], 400);
        }
        ProductReview::create(array_merge(
            $validator->validate(),
            [
                'user_id' => $user_id,
                'product_id' => $product->id,
                'user_name' => $user->name,
            ]
        ));
        $productReview = ProductReview::where('product_id', $product->id)->get();
        $productLength = $productReview->count();
        $totalRating = $productReview->sum('rating');

        $product->num_reviews = $productLength;
        $product->rating = $totalRating / $productLength;

        $product->save();

        return response()->json(['message' => 'Review added'], 201);
    }

    /**
     * Get top rated product
     *
     * @return JSON
     */
    public function topProducts()
    {
        $product = Product::orderBy('rating', 'DESC')->take(3)->get();
        return response()->json($product, 200);
    }
}
