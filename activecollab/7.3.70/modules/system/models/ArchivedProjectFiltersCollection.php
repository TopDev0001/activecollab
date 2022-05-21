<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ArchivedProjectFiltersCollection extends CompositeCollection
{
    use IWhosAsking;

    private string $projects_view;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * @var string
     */
    private $timestamp_hash = false;

    /**
     * @var array|bool
     */
    private $projects_collection = false;

    private function getArchivedProjectsIds(): array
    {
        if ($this->getWhosAsking()->isOwner()) {
            $project_ids = DB::executeFirstColumn('SELECT id FROM projects WHERE is_trashed = ? AND completed_on IS NOT NULL', false);
        } else {
            $project_ids = DB::executeFirstColumn('SELECT projects.id FROM projects 
                    JOIN project_users ON projects.id = project_users.project_id 
                    WHERE projects.is_trashed = ? AND projects.completed_on IS NOT NULL AND project_users.user_id = ?',
                false,
                $this->getWhosAsking()->getId()
            );
        }

        return $project_ids ?? [];
    }

    public function execute()
    {
        $project_ids = $this->getArchivedProjectsIds();

        return [
            'company_ids' => $project_ids ? DB::executeFirstColumn('SELECT company_id FROM projects WHERE id IN (?) GROUP BY company_id', $project_ids) : [],
            'category_ids' => $project_ids ? DB::executeFirstColumn('SELECT category_id FROM projects WHERE id IN (?) GROUP BY category_id', $project_ids) : [],
            'label_ids' => $project_ids ? DB::executeFirstColumn('SELECT label_id FROM projects WHERE id IN (?) GROUP BY label_id', $project_ids) : [],
            'leader_ids' => $project_ids ? DB::executeFirstColumn('SELECT leader_id FROM projects WHERE id IN (?) GROUP BY leader_id', $project_ids) : [],
        ];
    }

    public function getModelName(): string
    {
        return Projects::class;
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
                $this->getProjectsTimestampHash()
            );
        }

        return $this->timestamp_hash;
    }

    public function count()
    {
        if ($archived_projects = $this->getArchivedProjectsIds()) {
            return count($archived_projects);
        }

        return 0;
    }

    private function getProjectsTimestampHash()
    {
        if ($this->count() > 0) {
            return sha1(
                DB::executeFirstCell(
                    "SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM projects WHERE id IN (?)",
                    $this->getArchivedProjectsIds()
                )
            );
        }

        return sha1($this->getModelName());
    }
}
