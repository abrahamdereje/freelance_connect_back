<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', 'in:release,refund'],
            'details' => ['required', 'string'],
        ];
    }
}
