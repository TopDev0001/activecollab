<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\ModuleFactory;

use Angie\Inflector;
use AngieModule;
use LogicException;

class ModuleFactory implements ModuleFactoryInterface
{
    private string $modules_path;

    public function __construct(string $modules_path)
    {
        $this->modules_path = $modules_path;
    }

    public function createModule(string $module_name): AngieModule
    {
        $module_class = Inflector::camelize($module_name) . 'Module';
        $module_path = sprintf('%s/%s/%s.php', $this->modules_path, $module_name, $module_class);

        require_once $module_path;

        $namespaced_class_name = sprintf(
            '\\ActiveCollab\\Module\\%s\\%s',
            Inflector::camelize($module_name),
            $module_class
        );

        if (class_exists($namespaced_class_name, false)) {
            return new $namespaced_class_name(true, true);
        } elseif (class_exists($module_class, false)) {
            return new $module_class(true, true);
        }

        throw new LogicException(sprintf('Module class not found for %s module.', $module_name));
    }
}
