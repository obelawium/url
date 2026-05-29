<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Data;

use Carbon\Carbon;

readonly class UrlFilterDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param string|null $deviceType
     */
    public function __construct(
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
        public ?string $deviceType = null
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
            startDate: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            endDate: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            deviceType: $data['device_type'] ?? null
        );
    }
}
