<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectDiscussionsCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var Project
     */
    private $project;

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
     * @var int
     */
    private $discussions_count = false;

    /**
     * Construct the collection.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setPagination(1, 100);
    }

    public function getModelName(): string
    {
        return 'Discussions';
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    /**
     * @return string
     */
    private function getTimestampHash()
    {
        if ($this->timestamp_hash === false) {
            if ($this->getCurrentPage() && $this->getItemsPerPage()) {
                $limit = ' LIMIT ' . (($this->getCurrentPage() - 1) * $this->getItemsPerPage()) . ', ' . $this->getItemsPerPage();
            } else {
                $limit = '';
            }

            return sha1(
                DB::executeFirstCell(
                    "SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM discussions WHERE project_id = ? AND is_trashed = ? ORDER BY last_comment_on DESC, id DESC" . $limit,
                    $this->project->getId(),
                    false
                )
            );
        }

        return $this->timestamp_hash;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $result = ['discussions' => [], 'comments' => []];

        $limit = $this->getItemsPerPage();
        $offset = ($this->getCurrentPage() - 1) * $limit;

        if ($this->getWhosAsking() instanceof Client) {
            $result['discussions'] = Discussions::findBySql(
                "SELECT * FROM discussions WHERE project_id = ? AND is_hidden_from_clients = ? AND is_trashed = ? ORDER BY last_comment_on DESC, id DESC LIMIT $offset, $limit",
                $this->project->getId(),
                false,
                false
            );
        } else {
            $result['discussions'] = Discussions::findBySql(
                "SELECT * FROM discussions WHERE project_id = ? AND is_trashed = ? ORDER BY last_comment_on DESC, id DESC LIMIT $offset, $limit",
                $this->project->getId(),
                false
            );
        }

        if (empty($result['discussions'])) {
            $result['discussions'] = [];
        } else {
            $discussion_ids = [];

            /** @var Discussion $discussion */
            foreach ($result['discussions'] as $discussion) {
                $discussion_ids[] = $discussion->getId();
            }

            if (count($discussion_ids)) {
                Comments::preloadCountByParents(Discussion::class, $discussion_ids);
                Attachments::preloadDetailsByParents(Discussion::class, $discussion_ids);

                $result['comments'] = $this->queryLatestComments($discussion_ids);
            }
        }

        return $result;
    }

    /**
     * Query latest comments.
     *
     * @return Comment[]
     */
    private function queryLatestComments(array $discussion_ids)
    {
        $result = [];

        if ($rows = DB::execute("SELECT comms.id, comms.parent_type, comms.parent_id, comms.body, comms.created_on, comms.created_by_id, comms.created_by_name, comms.created_by_email FROM comments AS comms INNER JOIN (
        SELECT parent_type, parent_id, MAX(created_on) AS max_created_on FROM comments
        WHERE (parent_type = 'Discussion' AND parent_id IN (?)) AND is_trashed = ? GROUP BY parent_type, parent_id) AS c ON comms.parent_type = c.parent_type AND comms.parent_id = c.parent_id AND comms.created_on = c.max_created_on;", $discussion_ids, false
        )
        ) {
            $rows->setCasting('created_on', DBResult::CAST_DATETIME);

            foreach ($rows as $row) {
                if (empty($result[$row['parent_type']])) {
                    $result[$row['parent_type']] = [];
                }

                $result[$row['parent_type']][$row['parent_id']] = $row;
                $result[$row['parent_type']][$row['parent_id']]['body_excerpt'] = str_excerpt(
                    $result[$row['parent_type']][$row['parent_id']]['body'],
                    150,
                    '...',
                    true
                );
            }
        }

        return $result;
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($this->discussions_count === false) {
            if ($this->getWhosAsking() instanceof Client) {
                $this->discussions_count = DB::executeFirstCell(
                    "SELECT COUNT(id) AS 'row_count' FROM discussions WHERE project_id = ? AND is_hidden_from_clients = ? AND is_trashed = ?",
                    $this->project->getId(),
                    false,
                    false
                );
            } else {
                $this->discussions_count = DB::executeFirstCell(
                    "SELECT COUNT(id) AS 'row_count' FROM discussions WHERE project_id = ? AND is_trashed = ?",
                    $this->project->getId(),
                    false
                );
            }
        }

        return $this->discussions_count;
    }
}
