<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie;

use Angie\Modules\AngieFrameworkInterface;
use AngieFrameworkModelInterface;

class ModuleModelFactory implements ModuleModelFactoryInterface
{
    public function createModuleModel(AngieFrameworkInterface $frameworkOrModule): ?AngieFrameworkModelInterface
    {
        $model_class = $frameworkOrModule->getModuleClassName() . 'Model';

        $legacy_model_file = $frameworkOrModule->getPath() . "/resources/$model_class.class.php";
        $model_file = $frameworkOrModule->getPath() . "/resources/$model_class.php";

        if (is_file($legacy_model_file)) {
            require_once $legacy_model_file;

            if (class_exists($model_class, false)) {
                return new $model_class($frameworkOrModule);
            }
        } elseif (is_file($model_file)) {
            require_once $model_file;

            $namespaced_model_class = $frameworkOrModule->getNamespacedModuleClass(
                'Resources',
                $model_class
            );

            if (class_exists($namespaced_model_class)) {
                return new $namespaced_model_class($frameworkOrModule);
            }
        }

        return null;
    }
}
