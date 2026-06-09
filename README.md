# Obelawium URL Management Module (`obelaw/ium-url`)

A modular core package for Obelawium (IUM), a Laravel ERP framework, designed for complete URL management, link shortening, click tracking, and privacy-safe link analytics.

## Core Features

- **Fluent API Integration**: Access all functionality fluently via the global helper: `ium()->url()`.
- **Strict Type Safety**: All operations leverage dedicated Data Objects for input/query mapping (e.g., `ShortenUrlData`, `TrackUrlData`, `UrlStatsData`, `UrlStatsData`).
- **Unique vs Total Visitors Analytics**: High-performance counting separating absolute clicks from unique visitor impressions (IP + User Agent distinct aggregation).
- **Privacy-Safe Tracking**: Automatically masks IPv4 and IPv6 addresses before storing them in compliance with GDPR.
- **Decoupled Device Parser**: Analyzes user agent headers to categorize clients into `desktop`, `mobile`, `tablet`, `robot`, or `other` without heavy third-party vendor dependencies.
- **Database Prefix Compliance**: Models and migrations extend Obelawium bases (`ModelBase` and `MigrationBase`) to comply with prefix-scoped table registries (`ium_url_*`).

---

## Directory Structure

```text
src/
├── Base/
│   ├── MigrationBase.php    # Base migration adding 'ium_url_' table prefix
│   └── ModelBase.php        # Base model adding 'ium_url_' table prefix
├── Data/
│   ├── ShortenUrlData.php    # DTO validating shortening parameters
│   ├── TrackUrlData.php      # DTO encapsulating visitor request context
│   ├── UrlStatsData.php     # DTO encapsulating analytics filter criteria
│   ├── UrlStatsData.php     # DTO representing stats filter criteria
│   └── UrlStatsResultData.php # Immutable stats report payload containing unique counts
├── Models/
│   ├── Link.php             # Eloquent model representing shortened links
│   └── Click.php            # Eloquent model logging redirect click events
├── Providers/
│   └── URLServiceProvider.php # Binds the UrlService macro on the ium() helper
├── Services/
│   └── UrlService.php       # Domain Service executing core business logic
└── Utils/
    ├── IpAnonymizer.php     # Cleans visitor IP logs
    └── UserAgentParser.php  # Parses device classifications from User Agents
```

---

## Usage Examples

### 1. Shorten a URL
Use the DTO constructor to validate arguments and shorten the link via the `shorten` service method:

```php
use Obelaw\Ium\Url\Data\ShortenUrlData;

// Shortens URL and returns an instance of Obelaw\Ium\Url\Models\Link
$link = ium()->url()->shorten(new ShortenUrlData(
    url: 'https://github.com/obelaw/ium',
    code: 'github',              // Optional custom code (will auto-generate 6-char random alphanumeric if null)
    expiresAt: now()->addMonth(), // Optional link expiration
    status: 'active'             // Initial status: active / inactive
));

echo $link->code; // "github"
```

### 2. Redirection & Click Tracking
Pass the visitor's request details from the HTTP layer to the decoupled tracking service.

```php
use Obelaw\Ium\Url\Data\TrackUrlData;

$trackDto = new TrackUrlData(
    ipAddress: request()->ip(),
    userAgent: request()->userAgent(),
    referrer: request()->headers->get('referer')
);

// Returns Link model and records click info if active and unexpired, otherwise returns null
$link = ium()->url()->track($code, $trackDto);

if ($link) {
    return redirect()->away($link->original_url);
}

abort(404);
```

### 3. Get Filtered Analytics
Retrieve access aggregates, device distributions, and click referrers. You can filter the results using date ranges and device types.

```php
use Obelaw\Ium\Url\Data\UrlStatsData;

$filter = new UrlStatsData(
    startDate: now()->subDays(30),
    deviceType: 'mobile'
);

$analytics = ium()->url()->analytics($link->id, $filter);

echo "Total Clicks: " . $analytics['total_clicks'];
print_r($analytics['clicks_by_device']);   // e.g., ['mobile' => 12]
print_r($analytics['clicks_by_referrer']); // e.g., ['Direct' => 5, 'github.com' => 7]
```

### 4. Fetch Link Statistics (Total vs Unique Visitors)
Get detailed, index-friendly aggregate counts separating total link clicks from unique visitors.

```php
use Obelaw\Ium\Url\Data\UrlStatsData;

$statsData = new UrlStatsData(
    linkId: $link->id,
    startDate: now()->subDays(14)
);

// Returns Obelaw\Ium\Url\Data\UrlStatsResultData
$report = ium()->url()->stats($statsData);

echo "Total Interactions: " . $report->totalClicks; // captures all clicks
echo "Unique Visitors: " . $report->uniqueClicks;    // captures unique IP + User Agent pairs
echo "Conversion Ratio: " . $report->uniqueRatio . "%";
```

---

## Running Tests

Verify everything runs correctly by launching the Pest test suite:

```bash
composer test
```
