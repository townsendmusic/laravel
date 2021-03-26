<?php

namespace App\Http\Controllers;

use App\Filters\ProductIndexFilter;
use App\Filters\ProductSearchFilter;
use App\Http\Resources\ProductResource;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ProductsController extends Controller
{
    public $storeId;

    public $perPage;

    protected $cacheSeconds = 60 * 60 * 24;

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
     * @param Request $request
     * @param null $term
     * @return AnonymousResourceCollection
     */
    public function search(StoreProduct $storeProduct, ProductSearchFilter $filters, Request $request, $term = null): AnonymousResourceCollection
    {
        if ($term) {
            $filters->add(['term' => $term]);
        }

        $products = Cache::remember($this->cacheKey($term, $request), $this->cacheSeconds , function () use ($storeProduct, $filters){
            return $storeProduct->filter($filters, $this->storeId)->paginate($this->perPage);
        });

        return (ProductResource::collection($products));
    }

    protected function cacheKey($term, $request): string
    {
        if (is_array($term)) {
            $term = implode("_", $term);
        }

        return 'product_search_' . $term . "_" . $request->get('only_pre_order');
    }
}
