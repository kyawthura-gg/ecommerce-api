<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['subCategories']]);
    }

    /**
     * get all sub category with category
     *
     * @return collection
     */
    public function subCategories()
    {
        $subCategories = SubCategory::with('category')->get();

        return response()->json($subCategories);
    }

    /**
     * get Sub cateogry details by slug
     *
     * @param string $slug
     * @return object
     */
    public function subCategoryBySlug($slug)
    {
        $subCategory  = SubCategory::where('slug', '=', $slug)->firstOrFail();

        return response()->json($subCategory, 200);
    }
    /**
     * Create new sub category
     *
     * @param object {} //client send empty object
     * @return Object
     */
    public function store()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $name = 'New Sub Cateogry';
            $slug = Str::slug($name, '-');
            $next = 2;

            // Loop until we can query for the slug and it returns false
            while (SubCategory::where('slug', '=', $slug)->first()) {
                $slug = $slug . '-' . $next;
                $next++;
            }

            $subCategory = SubCategory::create([
                'category_id' => 1,
                'slug' => $slug,
                'name' => $name
            ]);
            return response()->json($subCategory, 201);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }

    /**
     * Update the sub category resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return Object
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'category_id' => 'required',
            'slug' => 'required',
            'is_visible' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $subCategory = SubCategory::findOrFail($id);

        $checkCategorySlug = SubCategory::where('id', '!=', $id)
            ->where('slug', $request->input('slug'))->get();

        if (!$checkCategorySlug->isEmpty()) {
            return response()->json(['message' => 'The slug has already been taken.'], 400);
        }

        if ($subCategory && Auth::user()->is_admin) {

            $subCategory->update($validator->validated());
            return response()->json($subCategory, 200);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }

    /**
     * Remove the specified sub category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $subCategory = SubCategory::findOrFail($id);
            $subCategory->delete();
            return response()->json(['message' => 'Sub Category deleted'], 204);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }
}
