<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewee' => new UserResource($this->whenLoaded('reviewee')),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
