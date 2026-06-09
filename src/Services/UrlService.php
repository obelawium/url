<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Obelaw\Ium\Engine\ObelawConfigManager;
use Obelaw\Ium\Url\Data\ShortenUrlData;
use Obelaw\Ium\Url\Data\TrackUrlData;
use Obelaw\Ium\Url\Data\UrlFilterData;
use Obelaw\Ium\Url\Data\UrlStatsData;
use Obelaw\Ium\Url\Data\UrlStatsResultData;
use Obelaw\Ium\Url\Models\Link;
use Obelaw\Ium\Url\Utils\IpAnonymizer;
use Obelaw\Ium\Url\Utils\UserAgentParser;

class UrlService
{
    public function __construct(private ObelawConfigManager $config) {}

    /**
     * Shorten a URL.
     *
     * @param ShortenUrlData $dto
     * @return Link
     */
    public function shorten(ShortenUrlData $dto): Link
    {
        $code = $dto->code;

        // If custom code is provided, validate its uniqueness
        if (!empty($code)) {
            if (Link::where('code', $code)->exists()) {
                throw new InvalidArgumentException("The short code '{$code}' is already in use.");
            }
        } else {
            // Generate a unique 6-character short code
            do {
                $code = Str::random(6);
            } while (Link::where('code', $code)->exists());
        }

        return Link::create([
            'original_url' => $dto->url,
            'code' => $code,
            'status' => $dto->status,
            'expires_at' => $dto->expiresAt,
        ]);
    }

    /**
     * Track a click for a shortened URL code.
     *
     * @param string $code
     * @param TrackUrlData $dto
     * @return Link|null
     */
    public function track(string $code, TrackUrlData $dto): ?Link
    {
        $link = Link::where('code', $code)->first();

        // If link does not exist or is not valid (active and not expired), return null
        if (!$link || !$link->isValid()) {
            return null;
        }

        // Parse user agent and anonymize IP
        $deviceType = UserAgentParser::parse($dto->userAgent);
        $anonymizedIp = IpAnonymizer::anonymize($dto->ipAddress);

        // Record the click
        $link->clicks()->create([
            'ip_address' => $anonymizedIp,
            'user_agent' => $dto->userAgent,
            'device_type' => $deviceType,
            'referrer' => $dto->referrer,
        ]);

        return $link;
    }

    /**
     * Get analytics for a shortened URL.
     *
     * @param int $linkId
     * @param UrlStatsData|null $filter
     * @return array
     */
    public function analytics(int $linkId, ?UrlFilterData $filter = null): array
    {
        $link = Link::findOrFail($linkId);

        $query = $link->clicks();

        if ($filter) {
            if ($filter->startDate) {
                $query->where('created_at', '>=', $filter->startDate);
            }
            if ($filter->endDate) {
                $query->where('created_at', '<=', $filter->endDate);
            }
            if ($filter->deviceType) {
                $query->where('device_type', $filter->deviceType);
            }
        }

        // Aggregate analytics data
        $clicks = $query->get();

        $totalClicks = $clicks->count();

        $clicksByDevice = $clicks->groupBy('device_type')
            ->map(fn($item) => $item->count())
            ->toArray();

        $clicksByReferrer = $clicks->groupBy(function ($click) {
                if (empty($click->referrer)) {
                    return 'Direct';
                }
                // Parse host from referrer URL
                $parsed = parse_url($click->referrer, PHP_URL_HOST);
                return $parsed ?: 'Unknown';
            })
            ->map(fn($item) => $item->count())
            ->toArray();

        $recentClicks = $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'link' => $link,
            'total_clicks' => $totalClicks,
            'clicks_by_device' => $clicksByDevice,
            'clicks_by_referrer' => $clicksByReferrer,
            'recent_clicks' => $recentClicks,
        ];
    }

    /**
     * Get statistics and reporting metrics for a link.
     * Captures both total interactions and unique visitors (distinct IP + UA combinations).
     *
     * @param UrlStatsData $data
     * @return UrlStatsResultData
     */
    public function stats(UrlStatsData $data): UrlStatsResultData
    {
        $link = Link::findOrFail($data->linkId);

        $totalQuery = $link->clicks();
        
        if ($data->startDate) {
            $totalQuery->where('created_at', '>=', $data->startDate);
        }
        if ($data->endDate) {
            $totalQuery->where('created_at', '<=', $data->endDate);
        }

        $clicks = $totalQuery->get();
        $totalClicks = $clicks->count();

        // High-performance Unique Visitor calculation
        // Distinct count of combined (IP address, user agent) pairs
        $uniqueCountQuery = DB::table(function ($subQuery) use ($data) {
            $subQuery->from('ium_url_clicks')
                ->where('link_id', $data->linkId)
                ->select('ip_address', 'user_agent')
                ->distinct();

            if ($data->startDate) {
                $subQuery->where('created_at', '>=', $data->startDate);
            }
            if ($data->endDate) {
                $subQuery->where('created_at', '<=', $data->endDate);
            }
        }, 'distinct_clicks');

        $uniqueClicks = $uniqueCountQuery->count();

        // Calculate unique ratio (Unique Visitors relative to Total Clicks)
        $uniqueRatio = $totalClicks > 0 
            ? round(($uniqueClicks / $totalClicks) * 100, 2) 
            : 0.0;

        $clicksByDevice = $clicks->groupBy('device_type')
            ->map(fn($item) => $item->count())
            ->toArray();

        $clicksByReferrer = $clicks->groupBy(function ($click) {
                if (empty($click->referrer)) {
                    return 'Direct';
                }
                $parsed = parse_url($click->referrer, PHP_URL_HOST);
                return $parsed ?: 'Unknown';
            })
            ->map(fn($item) => $item->count())
            ->toArray();

        $recentClicks = $clicks->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->toArray();

        return new UrlStatsResultData(
            link: $link,
            totalClicks: $totalClicks,
            uniqueClicks: $uniqueClicks,
            uniqueRatio: $uniqueRatio,
            clicksByDevice: $clicksByDevice,
            clicksByReferrer: $clicksByReferrer,
            recentClicks: $recentClicks
        );
    }
}
