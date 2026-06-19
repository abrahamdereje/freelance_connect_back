<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'freelancer_id' => $this->freelancer_id,
            'job' => new JobResource($this->whenLoaded('job')),
            'freelancer' => new UserResource($this->whenLoaded('freelancer')),
            'cover_letter' => $this->cover_letter,
            'bid_amount' => $this->bid_amount,
            'estimated_duration_days' => $this->estimated_duration_days,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
