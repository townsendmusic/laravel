<?php

namespace App\Filters;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductFilter extends Filter
{
    protected array $filters = ['page', 'number', 'sort', 'section'];

    protected int $page = 1;

    public int $perPage = 8;

    protected string $sectionField = 'description';

    protected string $sectionCompare = 'LIKE';

    public function default()
    {
        $this->builder->select([
            "store_products.id",
            "artist_id",
            "type",
            "display_name",
            "name",
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
        ]);

        $this->builder->forPage($this->page, $this->perPage);

        $this->builder
            ->where('store_products.deleted', 0)
            ->where('store_products.available', 1);

        //check the launch date
        if (!session()->has('preview_mode')) {
            $this->builder->whereDate('launch_date', '<', DB::raw('now()'));
        }

        //check the remove date
        $this->builder->where(function($query) {
            $query->where('remove_date', '0000-00-00 00:00:00')
                ->orWhere('remove_date', '>', DB::raw('now()'));
        });

        //check the disabled countries
        collect($this->getGeocodes())->each(function ($disabledCountry) {
            $this->builder->where('disabled_countries', "NOT LIKE", "%{$disabledCountry}%");
        });
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

    public function section($value)
    {
        if (is_numeric($value)) {
            $this->sectionField = 'id';
            $this->sectionCompare = '=';
        }

        if (Str::lower($value) !== 'all') {
            $this->builder
                ->join(
                    'store_products_section',
                    'store_products_section.store_product_id',
                    '=',
                    'store_products.id')
                ->join('sections', 'store_products_section.section_id', '=', 'sections.id')
                ->where('sections.' . $this->sectionField, $this->sectionCompare, $value)
                ->orderby('store_products_section.position')
                ->orderby('store_products.position')
                ->orderByDesc('store_products.release_date');
        } else {
            $this->builder->leftJoin('sections', 'sections.id', '=', DB::raw(-1))
                ->orderBy('store_products.position')
                ->orderByDesc('store_products.release_date');
        }
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

    public function getGeocodes()
    {
        //Return GB default for the purpose of the test
        return ['GB'];
    }
}
