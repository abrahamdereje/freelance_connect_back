<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Check is user is contract party in dispute service
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'milestone_id' => ['nullable', 'integer', 'exists:milestones,id'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'evidence' => ['nullable', 'file', 'max:10240'], // 10MB limit
        ];
    }
}
