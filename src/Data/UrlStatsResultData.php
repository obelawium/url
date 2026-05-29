<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Data;

use Obelaw\Ium\Url\Models\Link;

readonly class UrlStatsResultData
{
    /**
     * Create a new UrlStatsResultData instance.
     *
     * @param Link $link
     * @param int $totalClicks
     * @param int $uniqueClicks
     * @param float $uniqueRatio
     * @param array $clicksByDevice
     * @param array $clicksByReferrer
     * @param array $recentClicks
     */
    public function __construct(
        public Link $link,
        public int $totalClicks,
        public int $uniqueClicks,
        public float $uniqueRatio,
        public array $clicksByDevice,
        public array $clicksByReferrer,
        public array $recentClicks
    ) {}
}
