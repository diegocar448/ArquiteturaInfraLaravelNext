<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'cnpj' => $this->cnpj,
            'name' => $this->name,
            'url' => $this->url,
            'email' => $this->email,
            'logo' => $this->logo,
            'active' => $this->active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}