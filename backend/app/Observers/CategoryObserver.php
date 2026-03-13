<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryObserver
{
    public function creating(Category $category): void
    {
        if (empty($category->uuid)) {
            $category->uuid = (string) Str::uuid();
        }

        if (empty($category->url)) {
            $category->url = Str::slug($category->name);
        }
    }

    public function updating(Category $category): void
    {
        if ($category->isDirty('name') && ! $category->isDirty('url')) {
            $category->url = Str::slug($category->name);
        }
    }
}
