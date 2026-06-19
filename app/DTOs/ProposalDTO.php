<?php

namespace App\DTOs;

class ProposalDTO
{
    public function __construct(
        public readonly string $cover_letter,
        public readonly float $bid_amount,
        public readonly int $estimated_duration_days
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cover_letter: $data['cover_letter'],
            bid_amount: (float) $data['bid_amount'],
            estimated_duration_days: (int) $data['estimated_duration_days']
        );
    }
}
