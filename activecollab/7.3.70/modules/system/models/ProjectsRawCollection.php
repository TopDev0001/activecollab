<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectsRawCollection extends CompositeCollection
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

    /**
     * @var array|bool
     */
    private $projects_ids = false;

    /**
     * @var bool|string
     */
    private $conditions = false;

    public function &setProjectsView(string $projects_view): self
    {
        $this->projects_view = $projects_view;

        return $this;
    }

    private function getCommonConditions(): array
    {
        $conditions = [
            DB::prepare(
                'is_trashed = ?',
                false
            ),
        ];

        return $conditions;
    }

    private function getActiveProjectsIds(): array
    {
        $conditions = array_merge(
            $this->getCommonConditions(),
            [DB::prepare('completed_on IS NULL')]
        );

        $ids = DB::executeFirstColumn(
            sprintf(
                'SELECT id FROM projects WHERE %s',
                implode(' AND ', $conditions)
            )
        );

        if ($ids && !$this->getWhosAsking()->isOwner()) {
            $user_can_access_ids = $this->getWhosAsking()->getProjectIds();
            $ids = array_map(
                function ($id) use ($user_can_access_ids) {
                    return !empty($user_can_access_ids) && in_array($id, $user_can_access_ids) ? $id : null;
                },
                $ids
            );
        }

        return $ids ?? [];
    }

    private function getProjectsCollection(array $projects_ids): ?DBResult
    {
        if ($this->projects_collection === false) {
            if (empty($projects_ids)) {
                $this->projects_collection = null;
            } else {
                $this->projects_collection = DB::execute(
                    'SELECT id, project_number, name, body, company_id, label_id, is_sample, category_id, leader_id, created_by_id, UNIX_TIMESTAMP(created_on) AS created_on, UNIX_TIMESTAMP(last_activity_on) AS last_activity_on
                        FROM projects
                        WHERE id IN (?)',
                    $projects_ids
                );
            }
        }

        return $this->projects_collection;
    }

    private function getProjectIds(): array
    {
        if ($this->projects_view === 'list') {
            // Just active (open) projects for now
            return $this->getActiveProjectsIds();
        }

        return [];
    }

    private function getTaskCollectionForProjects(array $project_ids): array
    {
        $result = [];

        if (!empty($project_ids)) {
            if ($this->whos_asking instanceof Client) {
                $result = DB::execute('SELECT project_id, !ISNULL(completed_on) AS is_completed, count(id) as total_tasks FROM tasks WHERE project_id IN (?) AND is_trashed = ? AND is_hidden_from_clients = ? GROUP BY project_id, is_completed', $project_ids, false, false);
            } else {
                $result = DB::execute('SELECT project_id, !ISNULL(completed_on) AS is_completed, count(id) as total_tasks FROM tasks WHERE project_id IN (?) AND is_trashed = ? GROUP BY project_id, is_completed', $project_ids, false);
            }
        }

        return $result ? $result->toArray() : [];
    }

    public function execute()
    {
        $projects_ids = $this->getProjectIds();
        $tasks = $this->getTaskCollectionForProjects($projects_ids);
        $projects = $this->getProjectsCollection($projects_ids);

        if (empty($projects)) {
            return [];
        }

        $result = [];

        foreach ($projects as $project) {
            $project_id = $project['id'];
            $result[$project_id] = [
                'id' => (int) $project['id'],
                'project_number' => (int) $project['project_number'],
                'name' => $project['name'],
                'body' => $project['body'],
                'company_id' => $project['company_id'],
                'label_id' => $project['label_id'],
                'is_sample' => $project['is_sample'],
                'category_id' => $project['category_id'],
                'leader_id' => $project['leader_id'],
                'last_activity_on' => (int) $project['last_activity_on'],
                'created_on' => (int) $project['created_on'],
                'created_by_id' => (int) $project['created_by_id'],
                'count_open_tasks' => 0,
                'count_completed_tasks' => 0,
            ];
        }

        foreach ($tasks as $task) {
            if (array_key_exists($task['project_id'], $result)) {
                if ($task['is_completed']) {
                    $result[$task['project_id']]['count_completed_tasks'] = (int) $task['total_tasks'];
                } else {
                    $result[$task['project_id']]['count_open_tasks'] = (int) $task['total_tasks'];
                }
            }
        }

        return array_values($result);
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
        if ($projects_ids = $this->getActiveProjectsIds()) {
            return count($projects_ids);
        }

        return 0;
    }

    private function getProjectsTimestampHash()
    {
        if ($this->count() > 0) {
            return sha1(
                DB::executeFirstCell(
                    "SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM projects WHERE id IN (?)",
                    $this->getActiveProjectsIds()
                )
            );
        }

        return sha1($this->getModelName());
    }
}
