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
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use Angie\Inflector;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class RouteMapper implements RouteMapperInterface
{
    private ?string $current_module;

    /**
     * @var Route[]
     */
    private array $routes = [];
    private array $mapped_resources = [];

    public function __construct(string $current_module = null)
    {
        $this->current_module = $current_module;
    }

    public function mapFromFile(string $file_path, string $current_module_name = null): void
    {
        $this->setCurrentModule($current_module_name);

        if (is_file($file_path)) {
            require $file_path;
        }

        $this->setCurrentModule(null);
    }

    /**
     * Map resource.
     *
     * Settings:
     *
     * - path - route path, converts underscors to - (team_members -> team-members)
     * - controller_name - name of the controller, defaults to resource name (team_members)
     * - id - name of the ID field, defaults to singular of resource name + '_id' (team_member_id)
     * - id_format - pattern that ID value needs to match, default is number
     */
    public function mapResource(
        string $resource_name,
        array $settings = null,
        callable $extend = null
    ): void
    {
        if (!$this->isValidResourceName($resource_name)) {
            throw new InvalidArgumentException(sprintf('Resource name "%s" is not valid.', $resource_name));
        }

        $id = empty($settings['id']) ? Inflector::singularize($resource_name) . '_id' : $settings['id'];
        $module = empty($settings['module']) ? $this->getCurrentModule() : $settings['module'];
        $controller = empty($settings['controller']) ? $resource_name : $settings['controller'];

        $id_format = empty($settings['id_format']) ? UrlMatcherInterface::MATCH_ID : $settings['id_format'];

        $c = [
            'name' => $resource_name,
            'path' => empty($settings['collection_path'])
                ? str_replace('_', '-', $resource_name)
                : $settings['collection_path'],
            'module' => $module,
            'controller' => $controller,
            'actions' => empty($settings['collection_actions'])
                ? self::COLLECTION_METHOD_TO_ACTION_MAP
                : $settings['collection_actions'],
            'requirements' => empty($settings['collection_requirements'])
                || !is_array($settings['collection_requirements'])
                ? null
                : $settings['collection_requirements'],
        ];

        $single_resource_name = Inflector::singularize($c['name']);

        $default_entity_requirements = [$id => $id_format];

        $s = [
            'name' => $single_resource_name,
            'path' => empty($settings['single_path']) ? "$c[path]/:$id" : $settings['single_path'],
            'module' => $module,
            'controller' => $controller,
            'actions' => empty($settings['single_actions'])
                ? self::ENTITY_METHOD_TO_ACTION_MAP
                : $settings['single_actions'],
            'requirements' => empty($c['requirements'])
                ? $default_entity_requirements
                : array_merge($c['requirements'], $default_entity_requirements),
        ];

        $this->map(
            $c['name'],
            $c['path'],
            [
                'module' => $c['module'],
                'controller' => $c['controller'],
                'action' => $c['actions'],
            ],
            $c['requirements']
        );

        $this->map(
            $s['name'],
            $s['path'],
            [
                'module' => $s['module'],
                'controller' => $s['controller'],
                'action' => $s['actions'],
            ],
            $s['requirements']
        );

        if ($extend) {
            call_user_func($extend, $c, $s);
        }

        $this->mapped_resources[$resource_name] = $single_resource_name;
    }

    public function map(
        string $name,
        string $route,
        array $defaults = null,
        array $requirements = null
    )
    {
        if (!empty($this->routes[$name])) {
            throw new LogicException(sprintf('Route "%s" already mapped.', $name));
        }

        $this->routes[$name] = new Route($name, $route, $this->getDefaultsForMap($defaults), $requirements);
    }

    private function getDefaultsForMap(?array $defaults): array
    {
        if (empty($defaults)) {
            $defaults = [
                'module' => $this->current_module,
            ];
        } else {
            if (array_key_exists('module', $defaults) && !$this->isValidModuleName($defaults['module'])) {
                throw new InvalidArgumentException(sprintf('Module name "%s" is not valid.', $defaults['module']));
            }

            if (array_key_exists('controller', $defaults) && !$this->isValidControllerName($defaults['controller'])) {
                throw new InvalidArgumentException(
                    sprintf('Controller name "%s" is not valid.', $defaults['controller'])
                );
            }

            if (array_key_exists('action', $defaults)) {
                if (is_string($defaults['action'])) {
                    if (!$this->isValidActionName($defaults['action'])) {
                        throw new InvalidArgumentException(
                            sprintf('Action name "%s" is not valid.', $defaults['action'])
                        );
                    }
                } elseif (is_array($defaults['action'])) {
                    foreach ($defaults['action'] as $action) {
                        if (!$this->isValidActionName($action)) {
                            throw new InvalidArgumentException(
                                sprintf('Action name "%s" is not valid.', $action)
                            );
                        }
                    }
                } else {
                    throw new RuntimeException('Action is expected to be a string or an array.');
                }
            }

            if (empty($defaults['module'])) {
                if ($this->current_module) {
                    $defaults['module'] = $this->current_module;
                } else {
                    throw new InvalidArgumentException('Module attribute is required.');
                }
            }
        }

        return $defaults;
    }

    private function isValidResourceName(string $resource_name): bool
    {
        return !empty($resource_name) && trim($resource_name) === $resource_name;
    }

    private function isValidModuleName(string $module_name): bool
    {
        return !empty($module_name) && trim($module_name) === $module_name;
    }

    private function isValidControllerName(string $controller_name): bool
    {
        return !empty($controller_name) && trim($controller_name) === $controller_name;
    }

    private function isValidActionName(string $action_name): bool
    {
        return !empty($action_name) && trim($action_name) === $action_name;
    }

    public function getCurrentModule(): ?string
    {
        return $this->current_module;
    }

    public function setCurrentModule(?string $current_module): void
    {
        $this->current_module = $current_module;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRoute(string $route_name): ?RouteInterface
    {
        return $this->routes[$route_name] ?? null;
    }

    public function getMappedResources(): array
    {
        return $this->mapped_resources;
    }

    public function isMappedResource(string $resource_name): bool
    {
        return array_key_exists($resource_name, $this->mapped_resources);
    }

    public function isMappedEntity(string $entity_name): bool
    {
        return in_array($entity_name, $this->mapped_resources);
    }
}
