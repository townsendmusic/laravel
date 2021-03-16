<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\store_products;

class ProductsController extends Controller
{
    public function show(store_products $products)
    {
        dump($products->sectionProducts(3,'T-Shirtss'));
    }
}
