<?php

namespace App\Http\Controllers;

use App\Filters\ProductIndexFilter;
use App\Filters\ProductSearchFilter;
use App\Http\Resources\ProductResource;
use App\Models\StoreProduct;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    /**
     * @param StoreProduct $storeProduct
     * @param ProductIndexFilter $filters
     * @param null $section
     * @return AnonymousResourceCollection
     */
    public function index(StoreProduct $storeProduct, ProductIndexFilter $filters, $section = null): AnonymousResourceCollection
    {
        if ($section) {
            $filters->add(['section' => $section]);
        }

        $products = $storeProduct->filter($filters, $this->storeId);

        return (ProductResource::collection($products->paginate($this->perPage)));
    }

    /**
     * @param StoreProduct $storeProduct
     * @param ProductSearchFilter $filters
     * @param null $term
     * @return AnonymousResourceCollection
     */
    public function search(StoreProduct $storeProduct, ProductSearchFilter $filters, $term = null): AnonymousResourceCollection
    {
        if ($term) {
            $filters->add(['term' => $term]);
        }

        $products = $storeProduct->filter($filters, $this->storeId);
        return (ProductResource::collection($products->paginate($this->perPage)));
    }
}
