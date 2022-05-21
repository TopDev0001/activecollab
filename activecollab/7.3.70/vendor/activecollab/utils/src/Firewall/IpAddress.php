<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Firewall;

use InvalidArgumentException;

class IpAddress implements IpAddressInterface
{
    /**
     * @var string
     */
    private $ip_address;

    /**
     * @var callable
     */
    private $network_filter;

    public function __construct(string $ip_address)
    {
        if (!$this->validateAddress($ip_address)) {
            throw new InvalidArgumentException("Value '$ip_address' is not a valid IP address.");
        }

        $network_filter = $this->prepareNetworkFilter($ip_address);

        if (!$network_filter) {
            throw new InvalidArgumentException("Value '$ip_address' is not a valid IP address.");
        }

        $this->ip_address = $ip_address;
        $this->network_filter = $network_filter;
    }

    public function getIpAddress(): string
    {
        return $this->ip_address;
    }

    public function isOnList(array $list): bool
    {
        foreach ($list as $list_rule) {
            if ($this->ip_address == $list_rule) {
                return true;
            } else {
                if (call_user_func($this->network_filter, $list_rule, $this->ip_address)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return network filter for the given list.
     *
     * @param  string        $ip_address
     * @return bool|callable
     */
    private function prepareNetworkFilter(string $ip_address)
    {
        $ipv4 = filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        $ipv6 = filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        if ($ipv4) {
            $result = function ($network, $ip_address) {
                // Wildcard
                if (strpos($network, '*')) {
                    $allowed_ip_arr = explode('.', $network);
                    $ip_arr = explode('.', $ip_address);
                    for ($i = 0; $i < count($allowed_ip_arr); ++$i) {
                        if ($allowed_ip_arr[$i] == '*') {
                            return true;
                        } else {
                            if (false == ($allowed_ip_arr[$i] == $ip_arr[$i])) {
                                return false;
                            }
                        }
                    }
                }

                // Mask or CIDR
                if (strpos($network, '/')) {
                    $tmp = explode('/', $network);
                    if (strpos($tmp[1], '.')) {
                        list($allowed_ip_ip, $allowed_ip_mask) = explode('/', $network);
                        $begin = (ip2long($allowed_ip_ip) & ip2long($allowed_ip_mask)) + 1;
                        $end = (ip2long($allowed_ip_ip) | (~ip2long($allowed_ip_mask))) + 1;
                        $ip = ip2long($ip_address);

                        return $ip >= $begin && $ip <= $end;
                    } else {
                        list($net, $mask) = explode('/', $network);

                        return (ip2long($ip_address) & ~((1 << (32 - $mask)) - 1)) == ip2long($net);
                    }
                }

                // Section
                if (strpos($network, '-')) {
                    list($begin, $end) = explode('-', $network);
                    $begin = ip2long($begin);
                    $end = ip2long($end);
                    $ip = ip2long($ip_address);

                    return $ip >= $begin && $ip <= $end;
                }

                // Single
                if (ip2long($network)) {
                    return ip2long($network) == ip2long($ip_address);
                }

                return false;
            };
        } elseif ($ipv6) {
            $result = function ($network, $ip_address) {

                // CIDR
                if (strpos($network, '/')) {

                    // Split in address and prefix length
                    list($firstaddrstr, $prefixlen) = explode('/', $network);

                    // Parse the address into a binary string
                    $firstaddrbin = inet_pton($firstaddrstr);

                    $unpacked = unpack('H*', $firstaddrbin);

                    // Convert the binary string to a string with hexadecimal characters
                    // unpack() can be replaced with bin2hex()
                    // unpack() is used for symmetry with pack() below
                    $firstaddrhex = reset($unpacked);

                    // Calculate the number of 'flexible' bits
                    $flexbits = 128 - $prefixlen;

                    // Build the hexadecimal string of the last address
                    $lastaddrhex = $firstaddrhex;

                    // We start at the end of the string (which is always 32 characters long)
                    $pos = 31;
                    while ($flexbits > 0) {
                        // Get the character at this position
                        $orig = substr($lastaddrhex, $pos, 1);

                        // Convert it to an integer
                        $origval = hexdec($orig);

                        // OR it with (2^flexbits)-1, with flexbits limited to 4 at a time
                        $newval = $origval | (pow(2, min(4, $flexbits)) - 1);

                        // Convert it back to a hexadecimal character
                        $new = dechex($newval);

                        // And put that character back in the string
                        $lastaddrhex = substr_replace($lastaddrhex, $new, $pos, 1);

                        // We processed one nibble, move to previous position
                        $flexbits -= 4;
                        $pos -= 1;
                    }

                    // Convert the hexadecimal string to a binary string
                    // Using pack() here
                    // Newer PHP version can use hex2bin()
                    $lastaddrbin = pack('H*', $lastaddrhex);

                    $ip_address = inet_pton($ip_address);

                    return (strlen($firstaddrbin) == strlen($lastaddrbin)) && ($ip_address >= $firstaddrbin && $ip_address <= $lastaddrbin);
                }

                // Single
                if (filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    return inet_pton($ip_address) == inet_pton($network);
                }

                return false;
            };
        } else {
            return false;
        }

        return $result;
    }

    private function validateAddress($ip_address): bool
    {
        return filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
            filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
}
