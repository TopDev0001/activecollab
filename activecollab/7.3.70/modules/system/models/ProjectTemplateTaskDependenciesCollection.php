<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateTaskDependenciesCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var ProjectTemplateTask
     */
    private $task;

    private $tag = '';

    /**
     * @var ModelCollection
     */
    private $parents;

    /**
     * @var ModelCollection
     */
    private $children;

    /**
     * @var string
     */
    private $timestamp_hash;

    public function &setTask(IProjectTemplateTaskDependency $taskDependency): self
    {
        $this->task = $taskDependency;

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
        return ProjectTemplateTaskDependencies::class;
    }

    public function getTag(IUser $user, $use_cache = true)
    {
        if (!$this->tag || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    private function getTimestampHash()
    {
        if (!$this->timestamp_hash) {
            $this->timestamp_hash = sha1(
                $this->getParentsCollection()->getTimestampHash('created_on') . '-' .
                $this->getChildrenCollection()->getTimestampHash('created_on')
            );
        }

        return $this->timestamp_hash;
    }

    private function getParentsCollection(): ModelCollection
    {
        if (!$this->parents) {
            $this->parents = ProjectTemplateElements::prepareCollection(
                'parents_for_' . $this->task->getId(),
                $this->getWhosAsking()
            );
        }

        return $this->parents;
    }

    private function getChildrenCollection(): ModelCollection
    {
        if (!$this->children) {
            $this->children = ProjectTemplateElements::prepareCollection(
                'children_for_' . $this->task->getId(),
                $this->getWhosAsking()
            );
        }

        return $this->children;
    }
}
