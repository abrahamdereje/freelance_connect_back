<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'role' => $this->role->value,
            'is_suspended' => $this->is_suspended,
            'is_online' => $this->isOnline(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'employer_profile' => $this->whenLoaded('employerProfile'),
            'freelancer_profile' => $this->whenLoaded('freelancerProfile'),
            'admin_profile' => $this->whenLoaded('adminProfile'),
            'reviews_received' => ReviewResource::collection($this->whenLoaded('reviewsReceived')),
        ];
    }
}
