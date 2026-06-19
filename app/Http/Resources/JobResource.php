<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'employer_id' => $this->employer_id,
            'employer'    => new UserResource($this->whenLoaded('employer')),
            'category'    => $this->whenLoaded('category'),
            'title'       => $this->title,
            'description' => $this->description,
            'budget'      => (float) $this->budget,
            'type'        => $this->type->value,
            'status'      => $this->status->value,
            'skills'      => $this->whenLoaded('skills'),
            'attachments' => $this->whenLoaded('attachments'),
            'proposals_count' => $this->whenCounted('proposals'),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
