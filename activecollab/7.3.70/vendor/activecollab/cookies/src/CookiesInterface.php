<?php

/*
 * This file is part of the Active Collab Cookies project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Cookies;

use ActiveCollab\Cookies\Adapter\CookieGetterInterface;
use ActiveCollab\Cookies\Adapter\CookieRemoverInterface;
use ActiveCollab\Cookies\Adapter\CookieSetterInterface;
use ActiveCollab\Encryptor\EncryptorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CookiesInterface
{
    public function creteGetter(string $name): CookieGetterInterface;

    public function exists(ServerRequestInterface $request, string $name): bool;
    public function get(
        ServerRequestInterface $request,
        string $name,
        $default = null,
        bool $decrypt = true
    );

    public function createSetter(
        string $name,
        $value,
        array $settings = []
    ): CookieSetterInterface;

    public function set(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $name,
        $value,
        array $settings = []
    ): array;

    public function createRemover(string $name): CookieRemoverInterface;
    public function remove(ServerRequestInterface $request, ResponseInterface $response, string $name): array;

    public function getDefaultTtl(): int;
    public function defaultTtl(int $value): CookiesInterface;

    public function getDomain(): string;
    public function domain(string $domain): CookiesInterface;

    public function getPath(): string;
    public function path(string $path): CookiesInterface;

    public function getSecure(): bool;
    public function secure(bool $secure): CookiesInterface;

    public function getPrefix(): string;
    public function prefix(string $prefix): CookiesInterface;

    public function getEncryptor(): ?EncryptorInterface;
    public function encryptor(EncryptorInterface $encryptor = null): CookiesInterface;

    public function configureFromUrl(string $url): CookiesInterface;
}
