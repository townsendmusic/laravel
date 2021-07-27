<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ProductsController extends Controller
{
    private $storeId;
    private $imagesDomain;
    private $isCacheSwitchedOff;

    public function __construct()
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;
        $this->imagesDomain = "https://img.tmstor.es";
        $this->isCacheSwitchedOff = (empty(getenv('CACHE_ACTIVE')) OR getenv('CACHE_ACTIVE')!=='true');
    }

    public function products(int $returnOnlyPreorderProducts = 0, int $limit = 8, int $offset = 1): JsonResponse
    {
        $cachedProducts = Cache::get('products_'.$returnOnlyPreorderProducts.'_'.$limit.'_'.$offset);

        if((!$cachedProducts) OR $this->isCacheSwitchedOff) {
            $products = DB::table('store_products')
                ->select('id', 'artist_id', 'type', 'display_name', 'name', 'launch_date', 'remove_date', 'description',
                    'available', 'price', 'euro_price', 'dollar_price', 'image_format', 'disabled_countries', 'release_date')
                ->where(['store_products.store_id' => $this->storeId, 'deleted' => '0', 'available' => 1])
                ->orderBy("position", "ASC")
                ->orderBy("release_date", "DESC")
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();

            $products = $this->processProductsResults($products, $returnOnlyPreorderProducts);
            Cache::put('products_'.$returnOnlyPreorderProducts.'_'.$limit.'_'.$offset, $products, now()->addDay());
            return response()->json($products);

        } else {

            return response()->json($cachedProducts);
        }


    }

    public function section($section = "", int $returnOnlyPreorderProducts = 0, int $limit = 8, int $offset = 1): JsonResponse
    {
        $cachedSectionProducts = Cache::get('products_'.$section.'_'.$returnOnlyPreorderProducts.'_'.$limit.'_'.$offset);

        if((!$cachedSectionProducts) OR $this->isCacheSwitchedOff) {
            $where = ['store_products.store_id' => $this->storeId, 'deleted' => '0', 'available' => 1, ['sections.description', 'like', $section]];
            if (is_numeric($section)) {
                $where = ['store_products.store_id' => $this->storeId, 'deleted' => '0', 'available' => 1, 'sections.id' => $section];
            }

            $products = DB::table('store_products')
                ->join('store_products_section', 'store_products_section.store_product_id', '=', 'store_products.id')
                ->join('sections', 'store_products_section.section_id', '=', 'sections.id')
                ->select('store_products.id', 'artist_id', 'type', 'display_name', 'name', 'launch_date', 'remove_date', 'store_products.description',
                    'available', 'price', 'euro_price', 'dollar_price', 'image_format', 'disabled_countries', 'release_date')
                ->where($where)
                ->orderBy("store_products_section.position", "ASC")
                ->orderBy("release_date", "DESC")
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();

            $products = $this->processProductsResults($products, $returnOnlyPreorderProducts);
            Cache::put('products_'.$section.'_'.$returnOnlyPreorderProducts.'_'.$limit.'_'.$offset, $products, now()->addDay());

            return response()->json($products);

        } else {

            return response()->json($cachedSectionProducts);
        }

    }

    public function search(string $searchTerm, int $returnOnlyPreorderProducts = 0, int $limit = 8, int $offset = 1): JsonResponse
    {
        $cachedProductsSearchResults = Cache::get('products_'.$searchTerm.'_'.$returnOnlyPreorderProducts.'_'.$limit.'_'.$offset);

        if((!$cachedProductsSearchResults) OR $this->isCacheSwitchedOff) {

            $products = DB::table('store_products')
                ->select('id', 'artist_id', 'type', 'display_name', 'name', 'launch_date', 'remove_date', 'description',
                    'available', 'price', 'euro_price', 'dollar_price', 'image_format', 'disabled_countries', 'release_date')
                ->where(['store_products.store_id' => $this->storeId, 'deleted' => '0', 'available' => 1])
                ->where('description', 'like', "%$searchTerm%")
                ->orderBy("position", "ASC")
                ->orderBy("release_date", "DESC")
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();

            $products = $this->processProductsResults($products, $returnOnlyPreorderProducts);
            Cache::put('products_'.$searchTerm.'_'.$returnOnlyPreorderProducts.'_'.$limit.'_'.$offset, $products, now()->addDay());

            return response()->json($products);

        } else {

            return response()->json($cachedProductsSearchResults);
        }
    }

    private function processProductsResults(array $products, int $returnOnlyPreorderProducts = 0): array
    {
        foreach ($products as $product) {

            if ($returnOnlyPreorderProducts === 1) {
                if ($this->hasProductReleased($product)) {
                    continue;
                } else {
                    if (!$this->hasProductReleased($product)) {
                        continue;
                    }
                }
            }

            if(! $this->hasProductLaunched($product)) {
                continue;
            }

            if(! $this->hasProductRemoved($product)) {
                continue;
            }

            if(! $this->isProductDisabledInGeoLocation($product)) {
                continue;
            }

            $price = $this->getCurrency($product);

            $newProducts = [];
            if ((int)$product->available === 1) {
                $artists = Artist::where('id', $product->artist_id)->get();

                $newProducts['image'] = (strlen($product->image_format) > 2) ?
                    $this->imagesDomain . "/" . $product->id . "." . $product->image_format :
                    $this->imagesDomain . "noimage.jpg";

                $newProducts['id'] = $product->id;
                $newProducts['artist'] = $artists;
                $newProducts['title'] = strlen($product->display_name) > 3 ? $product->display_name : $product->name;
                $newProducts['description'] = $product->description;
                $newProducts['price'] = $price;
                $newProducts['format'] = $product->type;
                $newProducts['release_date'] = $product->release_date;
            }
        }
        return empty($newProducts) ? array() : $newProducts;
    }

    private function hasProductLaunched($product):bool {
        if ($product->launch_date != "0000-00-00 00:00:00" && !isset($_SESSION['preview_mode'])) {
            return (strtotime($product->launch_date) > time()) ? false : true;
        }
    }

    private function hasProductReleased($product):bool {

        if ($product->release_date != "0000-00-00 00:00:00") {
            return (strtotime($product->release_date) < time()) ? true : false;
        }
        return false;
    }

    private function hasProductRemoved($product):bool {
        if ($product->remove_date != "0000-00-00 00:00:00") {
            return (strtotime($product->remove_date) < time()) ? true : false;
        }
        return false;
    }

    private function getCurrency($product) {
        switch (session(['currency'])) {
            case "USD":
                return $product->dollar_price;
            case "EUR":
                return $product->euro_price;
            default :
                return $product->price;
        }
    }

    private function isProductDisabledInGeoLocation($product) {
        if ($product->disabled_countries != '') {
            $countries = explode(',', $product->disabled_countries);
            $geocode = $this->getGeocode();

            return (in_array($geocode['country'], $countries)) ? true : false;
        }
        return false;
    }

    private function getGeocode()
    {
        //Return GB default for the purpose of the test
        return ['country' => 'GB'];
    }
}
