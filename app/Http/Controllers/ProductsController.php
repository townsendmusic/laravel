<?php

namespace App\Http\Controllers;

use App\Filters\ProductFilter;
use App\Http\Resources\ProductResource;
use App\Models\StoreProduct;
use App\store_products;

class ProductsController extends Controller
{
    public $storeId;

    public $perPage;

    public function __construct()
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        //todo check why builder->paginate() not working
        $this->perPage = 8;
    }

    public function index(StoreProduct $storeProduct, ProductFilter $filters)
    {
        $s = new store_products();

       // dump($s->sectionProducts($this->storeId, 'all', 8, 1));
        $products = $storeProduct->filter($filters, $this->storeId);


        //dd($products->dd());
        return (ProductResource::collection($products->paginate($this->perPage)));
    }
}
