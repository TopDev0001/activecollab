<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Factory;

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Urls\ExternalUrl;
use ActiveCollab\Foundation\Urls\InternalUrl;
use ActiveCollab\Foundation\Urls\ModalArguments\ModalArguments;
use ActiveCollab\Foundation\Urls\ModalArguments\ModalArgumentsInterface;
use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssemblerInterface;
use ActiveCollab\Foundation\Urls\Services\WarehouseUrl;
use ActiveCollab\Foundation\Urls\UrlInterface;
use InvalidArgumentException;

class UrlFactory implements UrlFactoryInterface
{
    private UrlAssemblerInterface $url_assembler;
    private RootUrlInterface $root_url;

    public function __construct(UrlAssemblerInterface $url_assembler, RootUrlInterface $root_url)
    {
        $this->url_assembler = $url_assembler;
        $this->root_url = $root_url;
    }

    public function createFromUrl(string $url): UrlInterface
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !$this->isProxyUrl($url)) {
            throw new InvalidArgumentException(sprintf('Value "%s" is not a valid URL', $url));
        }

        $parsed_url = parse_url($url);

        if (empty($parsed_url) || !is_array($parsed_url)) {
            $parsed_url = [];
        }

        if ($this->root_url->isInternalUrl($url) || $this->isProxyUrl($url)) {
            return new InternalUrl($url, $this->getModalArguments($parsed_url));
        }

        $domain = $this->getDomain($parsed_url);

        $configured_warehouse_url = defined('WAREHOUSE_URL') ? parse_url(WAREHOUSE_URL, PHP_URL_HOST) : '';
        $fallback_warehouse_url = 'warehouse.activecollab.com';

        switch ($domain) {
            case $configured_warehouse_url:
            case $fallback_warehouse_url:
                return new WarehouseUrl($url);
            default:
                return new ExternalUrl($url, $domain);
        }
    }

    private function getDomain(array $parsed_url): string
    {
        $domain = array_key_exists('host', $parsed_url) && is_string($parsed_url['host'])
            ? $parsed_url['host']
            : '';

        if (str_starts_with($domain, 'www.')) {
            $domain = mb_substr($domain, 4);
        }

        return $domain;
    }

    private function getModalArguments(array $parsed_url): ?ModalArgumentsInterface
    {
        if (!empty($parsed_url['query'])) {
            $query = [];

            parse_str(
                str_replace(
                    '&amp;',
                    '&',
                    $parsed_url['query']
                ),
                $query
            );

            if (!empty($query['modal'])) {
                $modal_bits = explode('-', $query['modal']);
                $modal_bits_count = count($modal_bits);

                if ($modal_bits_count >= 3) {
                    for ($i = 1; $i < count($modal_bits); $i++) {
                        if (!ctype_digit($modal_bits[$i])) {
                            return null;
                        }
                    }

                    return new ModalArguments(
                        $this->url_assembler,
                        $modal_bits[0],
                        (int) $modal_bits[1],
                        (int) $modal_bits[2],
                        !empty($modal_bits[3]) ? (int) $modal_bits[3] : null
                    );
                } else {
                    return null;
                }
            }
        }

        return null;
    }

    private function isProxyUrl(string $url): bool
    {
        return str_starts_with($url, '/proxy.php?proxy');
    }
}
