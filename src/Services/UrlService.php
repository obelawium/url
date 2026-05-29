<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Services;

use Obelaw\Ium\Engine\ObelawConfigManager;
use Obelaw\Ium\Url\Data\ShortenUrlDTO;
use Obelaw\Ium\Url\Data\TrackUrlDTO;
use Obelaw\Ium\Url\Data\UrlFilterDTO;
use Obelaw\Ium\Url\Models\Link;
use Obelaw\Ium\Url\Models\Click;
use Obelaw\Ium\Url\Utils\UserAgentParser;
use Obelaw\Ium\Url\Utils\IpAnonymizer;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UrlService
{
    public function __construct(private ObelawConfigManager $config) {}

    /**
     * Shorten a URL.
     *
     * @param ShortenUrlDTO $dto
     * @return Link
     */
    public function shorten(ShortenUrlDTO $dto): Link
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
     * @param TrackUrlDTO $dto
     * @return Link|null
     */
    public function track(string $code, TrackUrlDTO $dto): ?Link
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
     * @param UrlFilterDTO|null $filter
     * @return array
     */
    public function analytics(int $linkId, ?UrlFilterDTO $filter = null): array
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
}
