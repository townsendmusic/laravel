<?php

namespace App\Filters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductIndexFilter extends ProductFilter
{
    protected array $filters = ['page', 'number', 'sort', 'section'];

    protected string $sectionField = 'description';

    protected string $sectionCompare = 'LIKE';

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
}
