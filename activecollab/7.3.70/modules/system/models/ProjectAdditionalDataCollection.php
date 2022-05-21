<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectAdditionalDataCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var bool|string
     */
    private $tag = false;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var bool|int[]
     */
    private $completed_task_ids = false;

    /**
     * @var bool|int[]
     */
    private $trashed_task_ids = false;

    /**
     * @var int|null
     */
    private $task_max_updated_on = null;

    public function &setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function execute()
    {
        return [
            'label_ids' => Labels::getLabelIdsByProject($this->project),
            'completed_task_ids' => $this->getCompletedTaskIds(),
            'trashed_task_ids' => $this->getTrashedTaskIds(),
            'completed_task_count' => count($this->getCompletedTaskIds()),
            'task_max_updated_on' => $this->getTaskMaxUpdateOn(),
            'time_tracked_directly_on_project' => TimeRecords::sumDirectlyOnProject($this->project->getId()),
        ];
    }

    public function count()
    {
        return 0;
    }

    public function getModelName(): string
    {
        return Projects::class;
    }

    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits(
                $user->getEmail(),
                sha1(
                    $this->project->getUpdatedOn()->toMySQL() . '-' .
                    $this->getTaskMaxUpdateOn()
                )
            );
        }

        return $this->tag;
    }

    private function getCompletedTaskIds(): array
    {
        if ($this->completed_task_ids === false) {
            $ids = DB::executeFirstColumn(
                sprintf(
                    'SELECT id FROM tasks WHERE project_id = ? AND completed_on IS NOT NULL AND is_trashed = ? %s',
                    $this->getSqlConditionByUser()
                ),
                $this->project->getId(),
                false
            );

            $this->completed_task_ids = $ids ? $ids : [];
        }

        return $this->completed_task_ids;
    }

    private function getTrashedTaskIds(): array
    {
        if ($this->trashed_task_ids === false) {
            $ids = DB::executeFirstColumn(
                sprintf(
                    'SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ? %s',
                    $this->getSqlConditionByUser()
                ),
                $this->project->getId(),
                true
            );

            $this->trashed_task_ids = $ids ? $ids : [];
        }

        return $this->trashed_task_ids;
    }

    private function getTaskMaxUpdateOn(): ?int
    {
        if ($this->task_max_updated_on === null) {
            $update_on = DB::executeFirstCell(
                sprintf(
                    'SELECT MAX(updated_on) as "update_on" FROM tasks WHERE project_id = ? AND is_trashed = ? %s',
                    $this->getSqlConditionByUser()
                ),
                $this->project->getId(),
                false
            );

            $this->task_max_updated_on = $update_on ? (new DateTimeValue($update_on))->getTimestamp() : null;
        }

        return $this->task_max_updated_on;
    }

    private function getSqlConditionByUser(): string
    {
        return $this->getWhosAsking()->isClient()
            ? DB::prepare('AND is_hidden_from_clients = ?', false)
            : '';
    }
}
