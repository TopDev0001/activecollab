<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Utils\Dependency\ProjectTemplateDependencyResolverInterface;

/**
 * ProjectTemplateTaskDependencies class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectTemplateTaskDependencies extends BaseProjectTemplateTaskDependencies
{
    public static function prepareCollection($collection_name, $user = null)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'project_template_tasks_dependencies')) {
            return self::prepareProjectTemplateTasksDependencies($collection_name, $user);
        }

        return $collection;
    }

    private static function prepareProjectTemplateTasksDependencies($collection_name, $user)
    {
        $bits = explode('_', $collection_name);
        /** @var IProjectTemplateTaskDependency $task */
        if ($task = DataObjectPool::get(ProjectTemplateTask::class, array_pop($bits))) {
            return (new ProjectTemplateTaskDependenciesCollection($collection_name))
                ->setTask($task)
                ->setWhosAsking($user);
        } else {
            throw new InvalidParamError(
                'collection_name',
                $collection_name,
                'ProjectTemplateTask ID expected in collection name'
            );
        }
    }

    public static function findDependenciesByElementIds(array $task_ids)
    {
        if (empty($task_ids)) {
            return [];
        }

        $result = DB::execute(
            'SELECT * FROM project_template_task_dependencies WHERE parent_id IN (?) OR child_id IN (?)',
            $task_ids,
            $task_ids
        );

        return $result ? $result->toArray() : [];
    }

    public static function createDependency(ProjectTemplateTask $parent, ProjectTemplateTask $child)
    {
        $new_dependency = [
            'parent_id' => $parent->getId(),
            'child_id' => $child->getId(),
        ];

        $dependencies = AngieApplication::getContainer()->get(ProjectTemplateDependencyResolverInterface::class)->getProjectTemplateDependencies($parent->getTemplateId());

        if (count($dependencies)) {
            $dependencies[] = $new_dependency;
        }

        if (AngieApplication::cyclicDependencyResolver()->checkCyclicDependency($dependencies)) {
            throw new LogicException(lang('Circular dependency detected, action aborted.'));
        }

        return self::create($new_dependency);
    }

    public static function deleteDependency(ProjectTemplateTask $task_1, ProjectTemplateTask $task_2)
    {
        parent::delete(
            [
                '(parent_id = ? && child_id = ?) || (parent_id = ? && child_id = ?)',
                $task_1->getId(),
                $task_2->getId(),
                $task_2->getId(),
                $task_1->getId(),
            ]
        );
    }
}
