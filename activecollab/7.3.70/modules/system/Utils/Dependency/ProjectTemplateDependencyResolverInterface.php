<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Dependency;

use IProjectTemplateTaskDependency;
use ModelCollection;
use ProjectTemplateTaskDependenciesCollection;

interface ProjectTemplateDependencyResolverInterface
{
    public function getDependencies(IProjectTemplateTaskDependency $task): ProjectTemplateTaskDependenciesCollection;

    public function getDependencySuggestions(IProjectTemplateTaskDependency $task): ModelCollection;

    public function getProjectTemplateDependencies(int $project_id): array;

    public function deleteDependencies(IProjectTemplateTaskDependency $task): void;
}
