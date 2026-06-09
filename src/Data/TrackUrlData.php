<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Data;

readonly class TrackUrlData
{
    /**
     * Create a new DTO instance.
     *
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param string|null $referrer
     */
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $referrer = null
    ) {}

    /**
     * Create a DTO from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            referrer: $data['referrer'] ?? null
        );
    }
}
