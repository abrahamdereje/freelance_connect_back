<?php

namespace App\DTOs;

use App\Enums\JobType;

class JobDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly float $budget,
        public readonly JobType $type,
        public readonly int $category_id,
        public readonly array $skills = [],
        public readonly array $attachments = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'],
            budget: (float) $data['budget'],
            type: is_string($data['type']) ? JobType::from($data['type']) : $data['type'],
            category_id: (int) $data['category_id'],
            skills: $data['skills'] ?? [],
            attachments: $data['attachments'] ?? []
        );
    }
}
