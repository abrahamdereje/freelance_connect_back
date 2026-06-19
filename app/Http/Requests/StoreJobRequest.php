<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JobType;
use Illuminate\Validation\Rules\Enum;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployer() && !is_null($this->user()->email_verified_at);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:job_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'budget' => ['required', 'numeric', 'min:1'],
            'type' => ['required', new Enum(JobType::class)],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB limit
        ];
    }
}
