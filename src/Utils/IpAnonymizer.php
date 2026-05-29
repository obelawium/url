<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Utils;

class IpAnonymizer
{
    /**
     * Anonymizes an IP address (IPv4 or IPv6) by masking host bits.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public static function anonymize(?string $ipAddress): ?string
    {
        if (empty($ipAddress)) {
            return null;
        }

        // Validate IPv4
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/[0-9]+$/', '0', $ipAddress);
        }

        // Validate IPv6
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ipAddress);
            if ($packed === false) {
                return null;
            }

            // Mask the last 8 bytes (64 bits) of IPv6 address
            $maskedPacked = substr($packed, 0, 8) . str_repeat("\x00", 8);
            return inet_ntop($maskedPacked) ?: null;
        }

        return null;
    }
}
