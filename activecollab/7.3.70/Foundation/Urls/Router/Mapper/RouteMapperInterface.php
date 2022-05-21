<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Mapper;

use ActiveCollab\Foundation\Urls\Router\Route;
use ActiveCollab\Foundation\Urls\Router\RouteInterface;

interface RouteMapperInterface
{
    const COLLECTION_METHOD_TO_ACTION_MAP = [
        'GET' => 'index',
        'POST' => 'add',
    ];

    const ENTITY_METHOD_TO_ACTION_MAP = [
        'GET' => 'view',
        'PUT' => 'edit',
        'DELETE' => 'delete',
    ];

    public function mapFromFile(string $file_path, string $current_module_name = null): void;
    public function mapResource(string $resource_name, array $settings = [], callable $extend = null): void;
    public function map(string $name, string $route, array $defaults = null, array $requirements = null);

    /**
     * @return Route[]
     */
    public function getRoutes(): array;
    public function getRoute(string $route_name): ?RouteInterface;
    public function getMappedResources(): array;
    public function isMappedResource(string $resource_name): bool;
    public function isMappedEntity(string $entity_name): bool;

    public function getCurrentModule(): ?string;
    public function setCurrentModule(?string $current_module): void;
}
