<?php

declare(strict_types=1);

namespace Empinet\SupportLink;

class SupportLinkGenerator
{
    public function __construct(
        protected string $signingSecret,
        protected string $supportHubUrl,
    ) {}

    public static function make(string $signingSecret, string $supportHubUrl): self
    {
        return new self($signingSecret, $supportHubUrl);
    }

    public function generate(SignedPayload $payload, ?int $expiresAfterSeconds = 600): string
    {
        return $this->supportHubUrl.'?token='.SignedContext::generate(
            $payload->toArray(),
            $this->signingSecret,
            $expiresAfterSeconds,
        );
    }

    public function createSignedToken(SignedPayload $payload, ?int $expiresAfterSeconds = 600): string
    {
        return SignedContext::generate(
            $payload->toArray(),
            $this->signingSecret,
            $expiresAfterSeconds,
        );
    }
}
