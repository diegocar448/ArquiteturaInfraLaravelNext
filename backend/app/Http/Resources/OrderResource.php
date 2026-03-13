<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'identify' => $this->identify,
            'status' => $this->status,
            'total' => $this->total,
            'comment' => $this->comment,
            'table' => new TableResource($this->whenLoaded('table')),
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'price' => $product->pivot->price,
                        'qty' => $product->pivot->qty,
                        'subtotal' => number_format($product->pivot->qty * $product->pivot->price, 2, '.', ''),
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
