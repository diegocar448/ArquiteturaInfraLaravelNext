<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'url' => $this->url,
            'flag' => $this->flag,
            'image' => $this->image,
            'price' => $this->price,
            'description' => $this->description,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
