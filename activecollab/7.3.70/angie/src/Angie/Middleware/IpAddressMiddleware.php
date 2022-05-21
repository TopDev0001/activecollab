<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use Angie\Middleware\Base\Middleware;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * IP address extractor middleware.
 *
 * Implementation was adopted from:
 *
 * https://github.com/oscarotero/psr7-middlewares/blob/master/src/Middleware/ClientIp.php
 *
 * @package Angie\Middleware
 */
class IpAddressMiddleware extends Middleware
{
    /**
     * @var string
     */
    private $ip_addresses_attribute_name;

    /**
     * @var string
     */
    private $ip_address_attribute_name;

    /**
     * @var callable|null
     */
    private $on_ip_addresses_resolved;

    /**
     * @var array The trusted headers
     */
    private $trusted_headers = [
        'Forwarded',
        'Forwarded-For',
        'Client-Ip',
        'X-Forwarded',
        'X-Forwarded-For',
        'X-Cluster-Client-Ip',
    ];

    /**
     * IpAddressMiddleware constructor.
     *
     * @param string $ip_addresses_attribute_name
     * @param string $ip_address_attribute_name
     */
    public function __construct(
        $ip_addresses_attribute_name,
        $ip_address_attribute_name,
        callable $on_ip_addresses_resolved = null,
        array $override_trusted_headers = null,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($logger);

        foreach ([$ip_addresses_attribute_name, $ip_address_attribute_name] as $value_to_check) {
            if (!is_string($value_to_check) || empty($value_to_check)) {
                throw new InvalidArgumentException('IP addresses, and IP address attribute names are required.');
            }
        }

        $this->ip_addresses_attribute_name = $ip_addresses_attribute_name;
        $this->ip_address_attribute_name = $ip_address_attribute_name;

        $this->on_ip_addresses_resolved = $on_ip_addresses_resolved;

        if ($override_trusted_headers !== null) {
            $this->trusted_headers = $override_trusted_headers;
        }
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $ip_addresses = $this->scanRequestForIpAddresses($request);
        $prefered_ip_address = $this->getPreferredIpAddress($ip_addresses);

        $request = $request
            ->withAttribute($this->ip_addresses_attribute_name, $ip_addresses)
            ->withAttribute($this->ip_address_attribute_name, $prefered_ip_address);

        if ($this->on_ip_addresses_resolved) {
            call_user_func($this->on_ip_addresses_resolved, $prefered_ip_address, $ip_addresses);
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * Return the first IP address, if found.
     *
     * @param  string[]    $ip_addresses
     * @return string|null
     */
    private function getPreferredIpAddress(array $ip_addresses)
    {
        foreach ($ip_addresses as $ip_address) {
            return $ip_address;
        }

        return null;
    }

    /**
     * Detect and return all ips found.
     *
     * @return array
     */
    private function scanRequestForIpAddresses(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();
        $ips = [];

        if (!empty($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ips[] = $server['REMOTE_ADDR'];
        }

        foreach ($this->trusted_headers as $header_name) {
            $header = $request->getHeaderLine($header_name);

            if (!empty($header)) {
                foreach (array_map('trim', explode(',', $header)) as $ip) {
                    if ((array_search($ip, $ips) === false) && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return $ips;
    }
}
