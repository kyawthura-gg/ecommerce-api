<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['categories', 'subCategories', 'products']]);
    }

    /**
     * get all categoies
     *
     * @return JSON
     */
    public function categories()
    {
        $categories = Category::with('subCategories')->get();

        return response()->json($categories);
    }
    /**
     * Get proudcts by page size 10 and keywords
     *
     * @param Request $request
     * @return collection
     */
    public function products(Request $request)
    {
        $pageSize = 10;

        $page = (int)$request->query('pageNumber', 1);
        $categorySlug = $request->query('category', '');

        $count = Product::where('category_id', $categorySlug)
            ->orWhere('sub_category_id',  $categorySlug)->count();

        $products = Product::where('category_id', $categorySlug)
            ->orWhere('sub_category_id',  $categorySlug)
            ->offset($pageSize * ($page - 1))
            ->limit($pageSize)
            ->get();

        return response()->json([
            'products' => $products,
            'page' => $page,
            'pages' => round($count / $pageSize)
        ], 200);
    }
    /**
     * get  cateogry details by slug
     *
     * @param string $slug
     * @return JSON
     */
    public function categoryBySlug($slug)
    {
        $category  = Category::where('slug', '=', $slug)->firstOrFail();

        return response()->json($category, 200);
    }
    /**
     * Create new category
     *
     * @param object {} //client send empty object
     * @return JSON
     */
    public function store()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $name = 'New Cateogry';
            $slug = Str::slug($name, '-');
            $next = 2;

            // Loop until we can query for the slug and it returns false
            while (Category::where('slug', '=', $slug)->first()) {
                $slug = $slug . '-' . $next;
                $next++;
            }

            $category = Category::create([
                'slug' => $slug,
                'name' => $name
            ]);
            return response()->json($category, 201);
        }
        return response()->json(['message' => 'Something went wrong'], 400);
    }

    /**
     * Update the cateogry resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required',
            'is_visible' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $category = Category::findOrFail($id);

        $checkCategorySlug = Category::where('id', '!=', $id)
            ->where('slug', $request->input('slug'))->get();

        if (!$checkCategorySlug->isEmpty()) {
            return response()->json(['message' => 'The slug has already been taken.'], 400);
        }

        if ($category && Auth::user()->is_admin) {

            $category->update($validator->validated());
            return response()->json($category, 200);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $category = Category::findOrFail($id);

            $category->delete();
            return response()->noContent();
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }
}
