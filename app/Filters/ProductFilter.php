<?php

namespace App\Filters;

use Illuminate\Support\Facades\DB;

class ProductFilter extends Filter
{
    protected int $page = 1;

    public int $perPage = 8;

    protected array $fields = [
        "store_products.id",
        "artist_id",
        "type",
        "display_name",
        "store_products.name",
        "launch_date",
        "remove_date",
        "store_products.description",
        "available",
        "price",
        "euro_price",
        "dollar_price",
        "image_format",
        "disabled_countries",
        "release_date"
    ];

    public function default()
    {
        $this->builder->forPage($this->page, $this->perPage);

        $this->builder
            ->where('store_products.deleted', 0)
            ->where('store_products.available', 1);

        //check the launch date
        $this->areLaunched();

        //check the remove date
        $this->notRemoved();

        //check the disabled countries
        $this->isAvailableInCountry();
    }

    public function page($value)
    {
        $this->page = $value;

        $this->builder->forPage($this->page, $this->perPage);
    }

    public function number($value)
    {
        $this->perPage = $value;

        $this->builder->forPage($this->page, $this->perPage);
    }

    public function sort($value)
    {
        if ($value === 0) {
            $value = "position";
        }

        switch ($value) {

            case "az":
                $this->builder->orderBy('store_products.name');
                break;
            case "za":
                $this->builder->orderByDesc('store_products.name');
                break;
            case "low":
                $this->builder->orderBy('store_products.price');
                break;
            case "high":
                $this->builder->orderByDesc('store_products.price');
                break;
            case "old":
                $this->builder->orderBy('store_products.release_date');
                break;
            case "new":
                $this->builder->orderByDesc('store_products.release_date');
                break;
        }
    }

    protected function areLaunched()
    {
        if (!session()->has('preview_mode')) {
            $this->builder->whereDate('launch_date', '<', DB::raw('now()'));
        }
    }

    protected function notRemoved()
    {
        $this->builder->where(function ($query) {
            $query->where('remove_date', '0000-00-00 00:00:00')
                ->orWhere('remove_date', '>', DB::raw('now()'));
        });
    }

    protected function isAvailableInCountry()
    {
        collect($this->getGeocodes())->each(function ($disabledCountry) {
            $this->builder->where('disabled_countries', "NOT LIKE", "%{$disabledCountry}%");
        });
    }

    public function getGeocodes()
    {
        //Return GB default for the purpose of the test
        return ['GB'];
    }
}
