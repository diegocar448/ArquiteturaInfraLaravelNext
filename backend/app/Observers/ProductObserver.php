<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        if (empty($product->uuid)) {
            $product->uuid = (string) Str::uuid();
        }

        if (empty($product->url)) {
            $product->url = Str::slug($product->title);
        }
    }

    public function updating(Product $product): void
    {
        if ($product->isDirty('title') && !$product->isDirty('url')) {
            $product->url = Str::slug($product->title);
        }
    }
}