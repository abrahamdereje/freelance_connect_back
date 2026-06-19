<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isFreelancer() && !is_null($this->user()->email_verified_at);
    }

    public function rules(): array
    {
        return [
            'cover_letter' => ['required', 'string'],
            'bid_amount' => ['required', 'numeric', 'min:1'],
            'estimated_duration_days' => ['required', 'integer', 'min:1'],
        ];
    }
}
