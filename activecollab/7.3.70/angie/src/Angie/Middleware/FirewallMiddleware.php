<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use ActiveCollab\Firewall\FirewallInterface;
use ActiveCollab\Firewall\IpAddress;
use Angie\Middleware\Base\Middleware;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Angie\Middleware
 */
class FirewallMiddleware extends Middleware
{
    /**
     * @var FirewallInterface
     */
    private $firewall;

    /**
     * @var string
     */
    private $ip_address_attribute_name;

    /**
     * FirewallMiddleware constructor.
     *
     * @param string $ip_address_attribute_name
     */
    public function __construct(FirewallInterface $firewall, $ip_address_attribute_name, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        if (!is_string($ip_address_attribute_name) || empty($ip_address_attribute_name)) {
            throw new InvalidArgumentException('IP address attribute name is required.');
        }

        $this->firewall = $firewall;
        $this->ip_address_attribute_name = $ip_address_attribute_name;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $ip_address = $this->getIpAddress($request);

        if ($this->firewall->shouldBlock(new IpAddress($ip_address))) {
            if ($this->getLogger()) {
                $this->getLogger()->notice('Address {ip_address} has been blocked by the firewall.', [
                    'ip_address' => $ip_address,
                ]);
            }

            return $response->withStatus(403);
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getIpAddress(ServerRequestInterface $request)
    {
        return (string) $request->getAttribute($this->ip_address_attribute_name);
    }
}
