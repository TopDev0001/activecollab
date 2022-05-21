<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Files and attachments data collection.
 *
 * @package activeCollab.modules.files
 * @subpackage models
 */
class ProjectFilesAndAttachmentsCollection extends CompositeCollection
{
    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;
    /**
     * @var Project
     */
    private $project;
    /**
     * Skip files and attachments that are hidden from clients.
     *
     * @var bool
     */
    private $skip_files_hidden_from_clients = false;

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
        return 'Files';
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
     * Return timestamp hash.
     *
     * @return string
     */
    private function getTimestampHash()
    {
        return sha1(
            DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM files WHERE " . $this->getFilesConditions()) . ',' .
            DB::executeFirstCell("SELECT GROUP_CONCAT(created_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM attachments WHERE " . $this->getAttachmentsConditions())
        );
    }

    /**
     * Return files conditions.
     *
     * @return string
     */
    private function getFilesConditions()
    {
        if ($this->getSkipFilesHiddenFromClients()) {
            return DB::prepare('project_id = ? AND is_hidden_from_clients = ? AND is_trashed = ?', $this->getProject()->getId(), false, false);
        } else {
            return DB::prepare('project_id = ? AND is_trashed = ?', $this->getProject()->getId(), false);
        }
    }

    /**
     * Skip files and attachments that are hidden from clients.
     *
     * @return bool
     */
    public function getSkipFilesHiddenFromClients()
    {
        return $this->skip_files_hidden_from_clients;
    }

    /**
     * Set if this collection should skip files and attachments that are hidden from clients.
     *
     * @param bool $value
     */
    public function setSkipFilesHiddenFromClients($value)
    {
        $this->skip_files_hidden_from_clients = (bool) $value;
    }

    // ---------------------------------------------------
    //  Filters
    // ---------------------------------------------------

    /**
     * Return project instance.
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set a project instance.
     *
     * @param Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * Return attachments conditions.
     *
     * @return string
     */
    private function getAttachmentsConditions()
    {
        if ($this->getSkipFilesHiddenFromClients()) {
            return DB::prepare('project_id = ? AND is_hidden_from_clients = ?', $this->getProject()->getId(), false);
        } else {
            return DB::prepare('project_id = ?', $this->getProject()->getId());
        }
    }

    /**
     * Run the query and return matching files, attachments and attachment parents.
     *
     * @return array
     */
    public function execute()
    {
        $offset = ($this->getCurrentPage() - 1) * $this->getItemsPerPage();
        $limit = $this->getItemsPerPage();

        $result = DB::execute("(SELECT id, type, NULL as 'parent_type', NULL AS 'parent_id', project_id, name, mime_type, size, location, md5, is_hidden_from_clients, is_trashed, original_is_trashed, trashed_on, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, raw_additional_properties FROM files WHERE " . $this->getFilesConditions() . ") UNION
        (SELECT id, type, parent_type, parent_id, project_id, name, mime_type, size, location, md5, is_hidden_from_clients, '0' AS is_trashed, NULL AS original_is_trashed, NULL AS trashed_on, created_on, created_by_id, created_by_name, created_by_email, created_on AS 'updated_on', created_by_id AS 'updated_by_id', created_by_name AS 'updated_by_name', created_by_email AS 'updated_by_email', raw_additional_properties FROM attachments WHERE " . $this->getAttachmentsConditions() . ") ORDER BY created_on DESC, id DESC LIMIT $offset, $limit");

        if ($result instanceof DBResult) {
            $result->returnObjectsByField('type');

            return ['files' => $result, 'attachment_parents' => $this->getAttachmentParents($result)];
        } else {
            return ['files' => [], 'attachment_parents' => []];
        }
    }

    /**
     * Get attachment parents from the $files result set.
     *
     * @return array
     */
    private function getAttachmentParents(DBResult $files)
    {
        $type_ids_map = [];
        $comment_ids = [];

        foreach ($files as $file) {
            if ($file instanceof Attachment) {
                $parent_type = $file->getParentType();

                if (empty($type_ids_map[$parent_type])) {
                    $type_ids_map[$parent_type] = [];
                }

                $type_ids_map[$parent_type][] = $file->getParentId();

                if ($parent_type == 'Comment') {
                    $comment_ids[] = $file->getParentId();
                }
            }
        }

        if (count($comment_ids)) {
            if ($rows = DB::execute('SELECT parent_type, parent_id FROM comments WHERE id IN (?)', $comment_ids)) {
                foreach ($rows as $row) {
                    $parent_type = $row['parent_type'];

                    if (empty($type_ids_map[$parent_type])) {
                        $type_ids_map[$parent_type] = [];
                    }

                    $type_ids_map[$parent_type][] = $row['parent_id'];
                }
            }
        }

        $result = count($type_ids_map) ? DataObjectPool::getByTypeIdsMap($type_ids_map) : null;

        if (empty($result)) {
            return [];
        }

        return $result;
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return
            (int) DB::executeFirstCell('SELECT COUNT(id) FROM files WHERE ' . $this->getFilesConditions()) +
            (int) DB::executeFirstCell('SELECT COUNT(id) FROM attachments WHERE ' . $this->getAttachmentsConditions());
    }
}
