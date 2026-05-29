<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Data;

use Carbon\Carbon;
use InvalidArgumentException;

readonly class ShortenUrlDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param string $url
     * @param string|null $code
     * @param Carbon|null $expiresAt
     * @param string $status
     */
    public function __construct(
        public string $url,
        public ?string $code = null,
        public ?Carbon $expiresAt = null,
        public string $status = 'active'
    ) {
        if (empty($this->url) || !filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The provided URL is invalid.');
        }

        if (!in_array($this->status, ['active', 'inactive'])) {
            throw new InvalidArgumentException('Status must be either active or inactive.');
        }
    }

    /**
     * Create a DTO from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'] ?? '',
            code: $data['code'] ?? null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            status: $data['status'] ?? 'active'
        );
    }
}
