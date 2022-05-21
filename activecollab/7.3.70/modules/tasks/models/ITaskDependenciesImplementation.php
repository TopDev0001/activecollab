<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

trait ITaskDependenciesImplementation
{
    private $parents_ids = false;
    private $children_ids = false;

    public function registerITaskDependenciesImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['open_dependencies'] = $this->getOpenDependencies();
            }
        );

        $this->registerEventHandler(
            'on_after_save',
            function ($is_new, $modifications) {
                if (!$is_new) {
                    // complete/uncomplete should clear cache
                    if (array_key_exists('completed_on', $modifications)) {
                        $this->clearDependenciesCache();
                    }
                    // move to project should remove dependencies and clear cache
                    if (array_key_exists('project_id', $modifications)) {
                        $this->removeDependencies();
                    }
                }
            }
        );

        if ($this instanceof ITrash) {
            $this->registerEventHandler(
                'on_after_move_to_trash',
                function ($bulk) {
                    if (!$bulk) {
                        $this->removeDependencies(); // non-bulk will remove dependencies and clear cache
                    } else {
                        $this->clearDependenciesCache(); // bulk will only clear cache
                    }
                }
            );

            $this->registerEventHandler(
                'on_after_restore_from_trash',
                function ($bulk) {
                    $this->clearDependenciesCache(); // restore from trash should clear cache
                }
            );
        }
    }

    public function getOpenDependencies(bool $use_cache = true): array
    {
        return AngieApplication::cache()->getByObject(
            $this,
            'open_dependencies',
            function () {
                return TaskDependencies::countOpenDependencies($this);
            },
            !$use_cache
        );
    }

    public function &cloneDependenciesTo(ITaskDependencies $to) : ITaskDependencies
    {
        $dependencies_to_create = [];

        foreach ($this->getParentsIds() as $id) {
            $dependencies_to_create[] = [
                'parent_id' => $id,
                'child_id' => $to->getId(),
            ];
        }
        foreach ($this->getChildrenIds() as $id) {
            $dependencies_to_create[] = [
                'parent_id' => $to->getId(),
                'child_id' => $id,
            ];
        }

        TaskDependencies::createMany($dependencies_to_create);

        $this->clearDependenciesCache();

        return $this;
    }

    public function getChildDependencies(): array
    {
        $children = DataObjectPool::getByIds(Task::class, $this->getChildrenIds());

        return $children ? $children : [];
    }

    public function getParentDependencies(): array
    {
        $parent = DataObjectPool::getByIds(Task::class, $this->getParentsIds());

        return $parent ? $parent : [];
    }

    private function removeDependencies(): void
    {
        $this->clearDependenciesCache(); // first delete cache for all parents and children
        TaskDependencies::deleteByTask($this); // then remove dependencies
    }

    private function clearDependenciesCache(): void
    {
        $task_ids = array_merge(
            $this->getParentsIds(),
            $this->getChildrenIds()
        );

        if (count($task_ids)) {
            /** @var Task[] $tasks */
            $tasks = array_merge(
                $this->getParentDependencies(),
                $this->getChildDependencies()
            );

            Tasks::clearCacheFor($task_ids);

            foreach ($tasks as $task) {
                $task->touch();
            }
        }
    }

    public function getParentsIds(): array
    {
        if ($this->parents_ids === false) {
            $this->parents_ids = DB::executeFirstColumn(
                'SELECT parent_id FROM task_dependencies WHERE child_id = ?',
                $this->getId()
            );
        }

        return $this->parents_ids ?? [];
    }

    public function getChildrenIds(): array
    {
        if ($this->children_ids === false) {
            $this->children_ids = DB::executeFirstColumn(
                'SELECT child_id FROM task_dependencies WHERE parent_id = ?',
                $this->getId()
            );
        }

        return $this->children_ids ?? [];
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
