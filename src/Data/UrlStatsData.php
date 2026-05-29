<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Data;

use Carbon\Carbon;

readonly class UrlStatsData
{
    /**
     * Create a new UrlStatsData instance.
     *
     * @param int $linkId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     */
    public function __construct(
        public int $linkId,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null
    ) {}

    /**
     * Create an instance from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            linkId: (int) ($data['link_id'] ?? 0),
            startDate: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            endDate: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null
        );
    }
}
