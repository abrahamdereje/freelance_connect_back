<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job' => new JobResource($this->whenLoaded('job')),
            'employer' => new UserResource($this->whenLoaded('employer')),
            'freelancer' => new UserResource($this->whenLoaded('freelancer')),
            'proposal' => new ProposalResource($this->whenLoaded('proposal')),
            'title' => $this->title,
            'total_amount' => $this->total_amount,
            'status' => $this->status->value,
            'start_date' => $this->start_date ? $this->start_date->toIso8601String() : null,
            'end_date' => $this->end_date ? $this->end_date->toIso8601String() : null,
            'milestones' => $this->whenLoaded('milestones'),
            'escrow' => $this->whenLoaded('escrow'),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
