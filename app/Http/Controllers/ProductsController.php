<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Section;
use App\Models\StoreProduct;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public $storeId;
    
    public function __construct()
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;
    }

    public function index(Request $request, Section $section)
    {
        $q = StoreProduct::with('artist', 'sections')
        ->where('store_id', $this->storeId)
        ->where('available', '>', 0)
        ->where(function ($q) {
            $q->where('launch_date', '=', '0000-00-00 00:00:00')
            ->orWhereDate('launch_date', '<', Carbon::now());
        })
        ->where(function ($q) {
            $q->where('remove_date', '=', '0000-00-00 00:00:00')
            ->orWhereDate('remove_date', '>', Carbon::now());
        })
        ->whereRaw("(',' || disabled_countries || ',') NOT LIKE '%,".session('geocode').",%'")
        ->where('deleted', '=', 0);
        
        if ($section->description) {
            $q->whereHas('sections', function ($q) use ($section) {
                $q->where('description', $section->description);
            });
        }

        //ordering and limiting
        $order = StoreProduct::order($request->order, $section);
        $limit = $request->limit ?? 8;

        $q->orderByRaw($order);
        
        //dump($q->toSql(), $q->getBindings());

        return $q->paginate($limit);
    }
}
