<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Dependency;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use DB;
use IProjectTemplateTaskDependency;
use ModelCollection;
use ProjectTemplateElements;
use ProjectTemplateTaskDependencies;
use ProjectTemplateTaskDependenciesCollection;
use User;

class ProjectTemplateDependencyResolver implements ProjectTemplateDependencyResolverInterface
{
    /**
     * @var User
     */
    private $user;

    public function __construct(AuthenticatedUserInterface $user)
    {
        $this->user = $user;
    }

    public function getDependencies(IProjectTemplateTaskDependency $task): ProjectTemplateTaskDependenciesCollection
    {
        return ProjectTemplateTaskDependencies::prepareCollection(
            'project_template_tasks_dependencies_for_' . $task->getId(),
            $this->user
        );
    }

    public function getDependencySuggestions(IProjectTemplateTaskDependency $task): ModelCollection
    {
        return ProjectTemplateElements::prepareCollection(
            "project_template_task_suggestion_for_{$task->getId()}",
            $this->user
        );
    }

    public function getProjectTemplateDependencies(int $project_id): array
    {
        $task_ids = DB::executeFirstColumn('SELECT id FROM project_template_elements WHERE template_id = ?', $project_id);
        $task_dependencies = DB::execute(
            'SELECT * FROM project_template_task_dependencies WHERE parent_id IN (?) OR child_id IN (?)',
            $task_ids,
            $task_ids
        );

        return $task_dependencies ? $task_dependencies->toArray() : [];
    }

    public function deleteDependencies(IProjectTemplateTaskDependency $task): void
    {
        ProjectTemplateTaskDependencies::delete(
            [
                'parent_id = ? || child_id = ?',
                $task->getId(),
                $task->getId(),
            ]
        );
    }
}
