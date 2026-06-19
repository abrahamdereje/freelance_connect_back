<?php

namespace App\DTOs;

class DisputeDTO
{
    public function __construct(
        public readonly int $contract_id,
        public readonly ?int $milestone_id,
        public readonly string $reason,
        public readonly string $description,
        public readonly ?string $evidence_path = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            contract_id: (int) $data['contract_id'],
            milestone_id: isset($data['milestone_id']) ? (int) $data['milestone_id'] : null,
            reason: $data['reason'],
            description: $data['description'],
            evidence_path: $data['evidence_path'] ?? null
        );
    }
}
