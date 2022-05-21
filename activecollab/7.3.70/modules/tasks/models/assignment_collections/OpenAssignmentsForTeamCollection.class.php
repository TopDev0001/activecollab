<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class OpenAssignmentsForTeamCollection extends AssignmentsCollection
{
    private ?Team $team = null;
    private ?ModelCollection $tasks_collection = null;
    private ?ModelCollection $subtasks_collection = null;

    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @return $this
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;

        return $this;
    }

    public function getContextTimestamp(): string
    {
        return $this->team->getUpdatedOn()->toMySQL();
    }

    public function getModelName(): string
    {
        return 'Teams';
    }

    protected function getTasksCollections(): ModelCollection
    {
        if (empty($this->tasks_collection)) {
            if ($this->team instanceof Team && $this->getWhosAsking() instanceof User) {
                $this->tasks_collection = Tasks::prepareCollection('open_tasks_assigned_to_team_' . $this->team->getId(), $this->getWhosAsking());
            } else {
                throw new ImpossibleCollectionError("Invalid user and/or who's asking instance");
            }
        }

        return $this->tasks_collection;
    }

    protected function getSubtasksCollection(): ModelCollection
    {
        if (empty($this->subtasks_collection)) {
            if ($this->team instanceof Team && $this->getWhosAsking() instanceof User) {
                $this->subtasks_collection = Subtasks::prepareCollection('open_subtasks_assigned_to_team_' . $this->team->getId(), $this->getWhosAsking());
            } else {
                throw new ImpossibleCollectionError("Invalid user and/or who's asking instance");
            }
        }

        return $this->subtasks_collection;
    }
}
