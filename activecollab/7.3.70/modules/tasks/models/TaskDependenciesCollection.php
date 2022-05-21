<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class TaskDependenciesCollection extends CompositeCollection
{
    use IWhosAsking;

    private $task;

    private $tag = false;

    /**
     * @var ModelCollection
     */
    private $parents_collection = false;
    private $children_collection = false;

    private $timestamp_hash = false;

    public function &setTask(Task $task)
    {
        $this->task = $task;

        return $this;
    }

    public function execute()
    {
        return [
            'parents' => $this->getParentsCollection(),
            'children' => $this->getChildrenCollection(),
        ];
    }

    public function count()
    {
        return $this->getParentsCollection()->count() + $this->getChildrenCollection()->count();
    }

    public function getModelName(): string
    {
        return TaskDependencies::class;
    }

    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    private function getTimestampHash()
    {
        if ($this->timestamp_hash === false) {
            $this->timestamp_hash = sha1(
                $this->getParentsCollection()->getTimestampHash('updated_on') . '-' .
                $this->getChildrenCollection()->getTimestampHash('updated_on')
            );
        }

        return $this->timestamp_hash;
    }

    private function getParentsCollection()
    {
        if ($this->parents_collection === false) {
            $this->parents_collection = Tasks::prepareCollection(
                'task_dependencies_parents_task_' . $this->task->getId(),
                $this->getWhosAsking()
            );
        }

        return $this->parents_collection;
    }

    private function getChildrenCollection()
    {
        if ($this->children_collection === false) {
            $this->children_collection = Tasks::prepareCollection(
                'task_dependencies_children_task_' . $this->task->getId(),
                $this->getWhosAsking()
            );
        }

        return $this->children_collection;
    }
}
