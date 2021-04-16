<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StoreProduct extends Model
{
    use HasFactory;

    public $table = 'store_products';
    protected $appends = [
        'image',
        'formatted_price',
        'formatted_display_name',
        'format'
    ];

    public $imagesDomain = "https://img.tmstor.es/";

    //ACCESSORS
    public function getFormattedPriceAttribute()
    {
        switch (session('currency')) {
            case "USD":
                $price = $this->dollar_price;
                break;
            case "EUR":
                $price = $this->euro_price;
                break;
            default:
                $price = $this->price;
                break;
        }

        return $price;
    }

    public function getFormattedDisplayNameAttribute()
    {
        return $this->display_name ? $this->display_name : $this->name;
    }

    public function getFormatAttribute()
    {
        return $this->type;
    }

    public function getImageAttribute()
    {
        $image = ($this->image_format) ? "$this->id.".$this->image_format : "noimage.jpg";

        return $this->imagesDomain . $image;
    }

    //RELATIONSHIPS
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

    //METHODS
    public static function order($order, $section)
    {
        switch ($order) {
            case "az":
                $order = "lower(name) ASC";
                break;
            case "za":
                $order = "lower(name) DESC";
                break;
            case "low":
                $order = "price ASC";
                break;
            case "high":
                $order = "price DESC";
                break;
            case "old":
                $order = "release_date ASC";
                break;
            case "new":
                $order = "release_date DESC";
                break;

            default:
                if ($section->id) {
                    //$order = "store_products_section.position ASC, release_date DESC";
                    $order = "release_date DESC"; //TODO
                } else {
                    $order = "position ASC, release_date DESC";
                }
                break;
        }

        return $order;
    }
}
