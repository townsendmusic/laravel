<?php

namespace App\Models;

use App\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    use HasFactory;

    public $table = 'store_products';

    protected $imagesDomain = "https://img.tmstor.es/";

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(
            Section::class,
            'store_products_section',
            'store_product_id',
            'section_id',
            'id',
            'id'
        )
            ->withPivot('position')
            ->orderBy('position', 'ASC');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id', 'id');
    }

    public function scopeFilter(Builder $builder, Filter $filter, $storeId)
    {
        $filter->apply($builder);
        $builder->where('store_products.store_id', $storeId);

        return $builder;
    }

    public function title()
    {
        return Str::length($this->display_name) > 3 ? $this->display_name : $this->name;
    }

    public function price()
    {
        switch (session()->get('currency')) {
            case 'USD':
                return $this->dollar_price;

            default:
                return $this->euro_price;
        }
    }

    public function image()
    {
        if (Str::length($this->image_format) > 2) {
            return $this->imagesDomain . "{$this->id}." . $this->image_format;
        }

        return $this->imagesDomain . "noimage.jpg";
    }
}
