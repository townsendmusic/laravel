<?php

namespace App\Filters;

use Illuminate\Support\Facades\DB;

class ProductSearchFilter extends ProductFilter
{
    protected array $filters = ['term', 'only_pre_order'];

    public function term($value)
    {
        $this->builder
            ->join(
                'store_products_section',
                'store_products_section.store_product_id',
                '=',
                'store_products.id')
            ->join('sections', 'store_products_section.section_id', '=', 'sections.id')
            ->join('artists', 'store_products.artist_id', '=', 'artists.id')
            ->orderby('store_products_section.position')
            ->orderby('store_products.position')
            ->orderByDesc('store_products.release_date');

        if (is_string($value)) {
            $this->builder->where(function ($query) use ($value){
                $query->where('sections.description', "LIKE", "%{$value}%")
                    ->orWhere('store_products.name', 'LIKE', "%{$value}%")
                    ->orWhere('store_products.display_name', 'LIKE', "%{$value}%")
                    ->orWhere('store_products.type', 'LIKE', "%{$value}%")
                    ->orWhere('artists.name', 'LIKE', "%{$value}%");
            });
        }

        if (is_array($value)) {
            $this->builder->where(function ($query) use ($value){
                $query->whereBetween('store_products.price', $value)
                    ->orwhereBetween('store_products.dollar_price', $value)
                    ->orwhereBetween('store_products.euro_price', $value);
            });
        }
    }

    protected function onlyPreOrder() {
        $this->builder->where('store_products.release_date', '>', DB::raw('now()'));
    }
}
