<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\CheckCyclicDependencyResolver;

class CheckCyclicDependencyResolver implements CheckCyclicDependencyResolverInterface
{
    private $tasks = [];

    public function checkCyclicDependency(array $dependencies): bool
    {
        $this->tasks = [];

        if (empty($dependencies)) {
            return false;
        }

        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency['parent_id'], $this->tasks)) {
                self::addDependencyItem($dependency['parent_id'], $dependency['child_id']);
            } else {
                array_push($this->tasks[$dependency['parent_id']]['children'], $dependency['child_id']);
            }
            if (!array_key_exists($dependency['child_id'], $this->tasks)) {
                self::addDependencyItem($dependency['child_id'], 0);
            }
        }

        foreach ($this->tasks as $task) {
            if (self::hasCycle($task)) {
                return true;
            }
        }

        return false;
    }

    private function hasCycle($task): bool
    {
        if ($this->tasks[$task['id']]['visited'] && $this->tasks[$task['id']]['recursion']) {
            return true;
        }
        if (!$this->tasks[$task['id']]['visited']) {
            $this->tasks[$task['id']]['recursion'] = true;
            $this->tasks[$task['id']]['visited'] = true;
            if (count($this->tasks[$task['id']]['children']) > 0) {
                foreach ($this->tasks[$task['id']]['children'] as $child) {
                    if ($this->tasks[$child]['recursion']) {
                        return true;
                    } else {
                        $this->hasCycle($this->tasks[$child]);
                    }
                }
            }
        }

        $this->tasks[$task['id']]['recursion'] = false;

        return false;
    }

    private function addDependencyItem(int $parent_id, int $child_id): void
    {
        $this->tasks[$parent_id] = [
            'id' => $parent_id,
            'children' => $child_id ? [$child_id] : [],
            'visited' => false,
            'recursion' => false,
        ];
    }
}
