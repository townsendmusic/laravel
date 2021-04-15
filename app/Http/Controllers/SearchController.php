<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    /**
     * search
     *
     * @param [string] $search
     * @return json
     */
    public function search($search = null)
    {   
        $preorders = (strpos(url()->current(), 'preorders') !== false) ? true : false;

        $cache_time = Carbon::now()->addDays(1);
        $cache_key = '_search_' . ($preorders ? 'pre_' : '') . $search;

        $products = Cache::remember($cache_key, $cache_time, function () use ($search, $preorders) {
            $q = StoreProduct::with('artist');

            if($search) {
                $q->whereRaw("lower(name) like '%" . strtolower($search) . "%'")
                    ->orWhereRaw("lower(description) like '%" . strtolower($search) . "%'");
            }

            if ($preorders) {
                $q->whereDate('release_date', '>=', Carbon::now());
            }

            //dd($q->toSql(), $q->getBindings());
            return $q->get();
        }); 

        return $products;
    }
}
