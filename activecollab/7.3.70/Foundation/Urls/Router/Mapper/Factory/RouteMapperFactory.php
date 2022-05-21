<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Mapper\Factory;

use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapper;
use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;

class RouteMapperFactory implements RouteMapperFactoryInterface
{
    private $frameworks_path;
    private $framework_names;
    private $modules_path;
    private $module_names;

    public function __construct(
        string $frameworks_path,
        array $framework_names,
        string $modules_path,
        array $module_names
    )
    {
        $this->framework_names = $framework_names;
        $this->frameworks_path = $frameworks_path;
        $this->module_names = $module_names;
        $this->modules_path = $modules_path;
    }

    public function createMapper(): RouteMapperInterface
    {
        $mapper = new RouteMapper();

        foreach ($this->framework_names as $framework_name) {
            $mapper->mapFromFile(
                sprintf('%s/%s/resources/routes.php', $this->frameworks_path, $framework_name),
                $framework_name
            );
        }

        foreach ($this->module_names as $module_name) {
            $mapper->mapFromFile(
                sprintf('%s/%s/resources/routes.php', $this->modules_path, $module_name),
                $module_name
            );
        }

        return $mapper;
    }
}
