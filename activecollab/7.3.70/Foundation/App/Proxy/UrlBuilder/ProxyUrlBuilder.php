<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Proxy\UrlBuilder;

use ActiveCollab\Foundation\App\Proxy\ProxyRequestHandler;
use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use Angie\Inflector;
use Angie\Utils\ProxyActionResolver;
use InvalidArgumentException;
use ReflectionClass;

class ProxyUrlBuilder implements ProxyUrlBuilderInterface
{
    private RootUrlInterface $root_url;

    public function __construct(RootUrlInterface $root_url)
    {
        $this->root_url = $root_url;
    }

    public function buildUrl(
        string $proxy_class,
        string $module_name,
        array $params = []
    ): string
    {
        $proxy_name = $this->getShortProxyName($proxy_class);

        $url_params = [
            'proxy' => $proxy_name,
            'module' => $module_name,
            'invalidate' => $this->getInvalidationHash($proxy_class),
        ];

        if (!empty($params)) {
            $url_params = array_merge($url_params, $params);
        }

        $placeholder = (new ProxyActionResolver())->resolveActionPlaceholder($proxy_name);

        if (!empty($placeholder)) {
            $url_params['i'] = $placeholder;
        }

        return sprintf(
            '%s/proxy.php?%s',
            $this->root_url->getUrl(),
            http_build_query($url_params)
        );
    }

    public function getInvalidationHash(string $proxy_class): string
    {
        $reflection = $this->getProxyClassReflection($proxy_class);

        $hashes = [];

        do {
            $proxy_file = $reflection->getFileName();

            if (is_file($proxy_file)) {
                $hashes[] = md5_file($proxy_file);
            }
        } while ($reflection = $reflection->getParentClass());

        return sha1(implode('-', $hashes));
    }

    private function getProxyClassReflection(string $proxy_class): ReflectionClass
    {
        if (!class_exists($proxy_class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" is not a valid proxy class.', $proxy_class));
        }

        $reflection = new ReflectionClass($proxy_class);

        if (!$reflection->isSubclassOf(ProxyRequestHandler::class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" is not a valid proxy class.', $proxy_class));
        }

        return $reflection;
    }

    private array $short_proxy_names = [];

    private function getShortProxyName(string $proxy_class): string
    {
        if (empty($this->short_proxy_names[$proxy_class])) {
            if (strpos($proxy_class, '\\') !== false) {
                $bits = explode('\\', $proxy_class);
                $proxy_class = $bits[count($bits) - 1];
            }

            $this->short_proxy_names[$proxy_class] = str_replace(
                '_proxy',
                '',
                Inflector::underscore($proxy_class)
            );
        }

        return $this->short_proxy_names[$proxy_class];
    }
}
