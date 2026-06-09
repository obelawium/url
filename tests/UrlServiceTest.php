<?php

declare(strict_types=1);

use Carbon\Carbon;
use Obelaw\Ium\Url\Data\ShortenUrlData;
use Obelaw\Ium\Url\Data\TrackUrlData;
use Obelaw\Ium\Url\Data\UrlFilterData;
use Obelaw\Ium\Url\Data\UrlStatsData;
use Obelaw\Ium\Url\Data\UrlStatsResultData;
use Obelaw\Ium\Url\Models\Link;
use Obelaw\Ium\Url\Services\UrlService;

it('registers the url macro on the ium helper', function () {
    $urlService = ium()->url();

    expect($urlService)->toBeInstanceOf(UrlService::class);
});

it('can shorten a URL with auto-generated code', function () {
    $dto = new ShortenUrlData(url: 'https://google.com');
    $link = ium()->url()->shorten($dto);

    expect($link)->toBeInstanceOf(Link::class);
    expect($link->original_url)->toBe('https://google.com');
    expect($link->code)->toHaveLength(6);
    expect($link->status)->toBe('active');
    expect($link->expires_at)->toBeNull();
});

it('can shorten a URL with custom code', function () {
    $dto = new ShortenUrlData(url: 'https://google.com', code: 'goog');
    $link = ium()->url()->shorten($dto);

    expect($link->code)->toBe('goog');
});

it('prevents shortening with duplicate custom code', function () {
    $dto1 = new ShortenUrlData(url: 'https://google.com', code: 'dup');
    ium()->url()->shorten($dto1);

    $dto2 = new ShortenUrlData(url: 'https://yahoo.com', code: 'dup');
    ium()->url()->shorten($dto2);
})->throws(InvalidArgumentException::class, "The short code 'dup' is already in use.");

it('tracks a click and logs anonymized IP and device info', function () {
    $dto = new ShortenUrlData(url: 'https://google.com');
    $link = ium()->url()->shorten($dto);

    $trackDto = new TrackUrlData(
        ipAddress: '192.168.1.55',
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
        referrer: 'https://twitter.com/some/path'
    );

    $trackedLink = ium()->url()->track($link->code, $trackDto);

    expect($trackedLink)->not->toBeNull();
    expect($trackedLink->id)->toBe($link->id);

    $click = $link->clicks()->first();
    expect($click)->not->toBeNull();
    expect($click->ip_address)->toBe('192.168.1.0'); // Anonymized
    expect($click->device_type)->toBe('mobile');     // Mobile iPhone UA
    expect($click->referrer)->toBe('https://twitter.com/some/path');
});

it('does not track inactive or expired links', function () {
    // Inactive link
    $inactiveDto = new ShortenUrlData(url: 'https://google.com', status: 'inactive');
    $inactiveLink = ium()->url()->shorten($inactiveDto);

    $trackDto = new TrackUrlData(ipAddress: '127.0.0.1');
    $res = ium()->url()->track($inactiveLink->code, $trackDto);
    expect($res)->toBeNull();

    // Expired link
    $expiredDto = new ShortenUrlData(
        url: 'https://google.com',
        expiresAt: Carbon::now()->subDay()
    );
    $expiredLink = ium()->url()->shorten($expiredDto);

    $res2 = ium()->url()->track($expiredLink->code, $trackDto);
    expect($res2)->toBeNull();
});

it('returns correct analytics and respects filtering', function () {
    $dto = new ShortenUrlData(url: 'https://google.com');
    $link = ium()->url()->shorten($dto);

    // Track multiple clicks
    $directDesktop = new TrackUrlData(ipAddress: '10.0.0.5', userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/113.0.0.0');
    $referredMobile = new TrackUrlData(ipAddress: '172.16.0.4', userAgent: 'Mobile Android', referrer: 'https://github.com');
    $referredTablet = new TrackUrlData(ipAddress: '192.168.1.1', userAgent: 'iPad', referrer: 'https://github.com');

    ium()->url()->track($link->code, $directDesktop);
    ium()->url()->track($link->code, $referredMobile);
    ium()->url()->track($link->code, $referredTablet);

    // Retrieve analytics without filters
    $analytics = ium()->url()->analytics($link->id);

    expect($analytics['total_clicks'])->toBe(3);
    expect($analytics['clicks_by_device']['desktop'])->toBe(1);
    expect($analytics['clicks_by_device']['mobile'])->toBe(1);
    expect($analytics['clicks_by_device']['tablet'])->toBe(1);
    expect($analytics['clicks_by_referrer']['Direct'])->toBe(1);
    expect($analytics['clicks_by_referrer']['github.com'])->toBe(2);

    // Retrieve analytics with device filter
    $filterDto = new UrlFilterData(deviceType: 'mobile');
    $filteredAnalytics = ium()->url()->analytics($link->id, $filterDto);

    expect($filteredAnalytics['total_clicks'])->toBe(1);
    expect($filteredAnalytics['clicks_by_device']['mobile'])->toBe(1);
    expect(isset($filteredAnalytics['clicks_by_device']['desktop']))->toBeFalse();
});

it('calculates total vs unique clicks correctly and filters by date', function () {
    $dto = new ShortenUrlData(url: 'https://google.com');
    $link = ium()->url()->shorten($dto);

    $ua1 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/113.0.0.0';
    $ua2 = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) Mobile';

    // Click 1: User A (IP 1.1.1.1, UA 1)
    ium()->url()->track($link->code, new TrackUrlData(ipAddress: '1.1.1.1', userAgent: $ua1));
    // Click 2: User A again (IP 1.1.1.1, UA 1) -> Same IP + UA (non-unique)
    ium()->url()->track($link->code, new TrackUrlData(ipAddress: '1.1.1.1', userAgent: $ua1));

    // Click 3: User B (IP 1.1.1.1, UA 2) -> Same IP, different UA (Unique Visitor)
    ium()->url()->track($link->code, new TrackUrlData(ipAddress: '1.1.1.1', userAgent: $ua2));

    // Click 4: User C (IP 2.2.2.2, UA 1) -> Different IP, same UA (Unique Visitor)
    ium()->url()->track($link->code, new TrackUrlData(ipAddress: '2.2.2.2', userAgent: $ua1));

    // Execute stats retrieval
    $statsData = new UrlStatsData(linkId: $link->id);
    $result = ium()->url()->stats($statsData);

    expect($result)->toBeInstanceOf(UrlStatsResultData::class);
    expect($result->totalClicks)->toBe(4);
    expect($result->uniqueClicks)->toBe(3); // User A, User B, User C
    expect($result->uniqueRatio)->toBe(75.0); // 3 / 4 * 100
    expect($result->clicksByDevice['desktop'])->toBe(3); // UA 1 is desktop (3 times), UA 2 is mobile (1 time)
    expect($result->clicksByDevice['mobile'])->toBe(1);

    // Test with date filter (start date tomorrow should yield 0)
    $filteredStatsData = new UrlStatsData(
        linkId: $link->id,
        startDate: Carbon::now()->addDay()
    );
    $filteredResult = ium()->url()->stats($filteredStatsData);
    expect($filteredResult->totalClicks)->toBe(0);
    expect($filteredResult->uniqueClicks)->toBe(0);
    expect($filteredResult->uniqueRatio)->toBe(0.0);
});
