<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;
    protected $table = 'sub_categories';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function products()
    {
        return $this->belongsToMany('App\Models\Product');
    }
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
}
