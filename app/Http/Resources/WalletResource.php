<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'balance' => (float) $this->balance,
            'transactions' => TransactionResource::collection($this->when(
                $this->relationLoaded('transactions') || $request->user()->id === $this->user_id,
                fn() => \App\Models\Transaction::where('wallet_id', $this->id)->latest()->get()
            )),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
