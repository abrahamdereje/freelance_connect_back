<?php

namespace App\DTOs;

class MilestoneDTO
{
    public function __construct(
        public readonly string $title,
        public readonly float $amount,
        public readonly ?string $due_date = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            amount: (float) $data['amount'],
            due_date: $data['due_date'] ?? null
        );
    }
}
