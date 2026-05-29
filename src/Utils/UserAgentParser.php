<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Utils;

class UserAgentParser
{
    /**
     * Parse user agent to categorize device type.
     * Returns: desktop, mobile, tablet, robot, or other.
     *
     * @param string|null $userAgent
     * @return string
     */
    public static function parse(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'other';
        }

        $userAgent = strtolower($userAgent);

        // Detect robot/crawler first
        $bots = [
            'googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'slurp',
            'duckduckbot', 'crawler', 'spider', 'robot', 'curl', 'wget'
        ];
        foreach ($bots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return 'robot';
            }
        }

        // Detect tablet next
        $tablets = ['ipad', 'playbook', 'kindle', 'silk', 'tablet'];
        foreach ($tablets as $tablet) {
            if (str_contains($userAgent, $tablet)) {
                return 'tablet';
            }
        }

        // Detect mobile
        $mobiles = ['iphone', 'ipod', 'android', 'webos', 'blackberry', 'iemobile', 'opera mini', 'mobile'];
        foreach ($mobiles as $mobile) {
            if (str_contains($userAgent, $mobile)) {
                return 'mobile';
            }
        }

        // Detect desktop
        $desktops = ['windows nt', 'macintosh', 'linux', 'chrome', 'safari', 'firefox', 'edge'];
        foreach ($desktops as $desktop) {
            if (str_contains($userAgent, $desktop)) {
                return 'desktop';
            }
        }

        return 'other';
    }
}
