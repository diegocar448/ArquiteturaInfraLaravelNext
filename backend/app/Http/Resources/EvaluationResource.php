<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'client' => new ClientResource($this->whenLoaded('client')),
            'order' => [
                'id' => $this->order->id,
                'identify' => $this->order->identify,
            ],
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
