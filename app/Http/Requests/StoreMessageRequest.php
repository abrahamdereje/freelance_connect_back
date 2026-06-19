<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We will check conversation participation in controller policy
    }

    public function rules(): array
    {
        return [
            'message_text' => ['required', 'string'],
        ];
    }
}
