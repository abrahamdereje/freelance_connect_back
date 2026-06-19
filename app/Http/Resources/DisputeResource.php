<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'milestone' => $this->whenLoaded('milestone'),
            'raiser' => new UserResource($this->whenLoaded('raiser')),
            'reason' => $this->reason,
            'description' => $this->description,
            'evidence_path' => $this->evidence_path,
            'status' => $this->status->value,
            'resolution_details' => $this->resolution_details,
            'resolver' => new UserResource($this->whenLoaded('resolver')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
