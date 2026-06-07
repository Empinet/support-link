<?php

declare(strict_types=1);

namespace Empinet\SupportLink;

class SignedPayload
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly array $meta = [],
    ) {}

    public function toArray(): array
    {
        return array_merge(
            ['name' => $this->name, 'email' => $this->email],
            $this->meta,
        );
    }
}
