<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\CommentEvents\CommentCreatedEvent;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\CommentEvents\CommentUpdatedEvent;

class Comments extends BaseComments
{
    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'comments_for')) {
            $bits = explode('_', $collection_name);

            $page = array_pop($bits);
            array_pop($bits); // _page_

            [$parent_type, $parent_id] = explode('-', array_pop($bits));

            $parent = $parent_type && $parent_id && is_subclass_of($parent_type, 'DataObject') ? new $parent_type($parent_id) : null;

            if ($parent instanceof IComments) {
                $collection = parent::prepareCollection($collection_name, $user);

                $collection->setConditions('parent_type = ? AND parent_id = ? AND is_trashed = ?', $parent_type, $parent_id, false);
                $collection->setPagination($page, 30);

                $collection->setPreExecuteCallback(function ($ids) {
                    if ($ids && is_foreachable($ids)) {
                        Attachments::preloadDetailsByParents('Comment', $ids);
                        Reactions::preloadDetailsByParents('Comment', $ids);
                    }
                });

                return $collection;
            }
        }

        throw new InvalidParamError('collection_name', $collection_name);
    }

    /**
     * Prepare and return comment excerpt.
     *
     * @param  string $from
     * @return string
     */
    public static function getCommentExcerpt($from)
    {
        $result = $from ? str_excerpt($from, 100, '', true) : '';

        return $result ? $result : '--';
    }

    /**
     * @var array
     */
    private static $counts_by_parent = [];

    /**
     * Preload comments count for a given type and a list of ID-s.
     *
     * @param string $parent_type
     */
    public static function preloadCountByParents($parent_type, array $parent_ids)
    {
        self::$counts_by_parent[$parent_type] = [];

        if ($rows = DB::execute("SELECT parent_type, parent_id, COUNT(id) AS 'row_count' FROM comments WHERE parent_type = ? AND parent_id IN (?) AND is_trashed = ? GROUP BY parent_type, parent_id", $parent_type, $parent_ids, false)) {
            foreach ($rows as $row) {
                self::$counts_by_parent[$parent_type][$row['parent_id']] = $row['row_count'];
            }
        }

        if ($zeros = array_diff($parent_ids, array_keys(self::$counts_by_parent[$parent_type]))) {
            foreach ($zeros as $parent_with_zero_comments) {
                self::$counts_by_parent[$parent_type][$parent_with_zero_comments] = 0;
            }
        }
    }

    /**
     * Return number of untrashed comments for a given object.
     *
     * @param  DataObject|IComments $parent
     * @return int
     */
    public static function countByParent(IComments $parent)
    {
        $parent_type = get_class($parent);
        $parent_id = $parent->getId();

        if (isset(self::$counts_by_parent[$parent_type][$parent_id])) {
            return self::$counts_by_parent[$parent_type][$parent_id];
        } else {
            return DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM comments WHERE parent_type = ? AND parent_id = ? AND is_trashed = ?', $parent_type, $parent_id, false);
        }
    }

    public static function countByParentTypeAndParentId(string $parent_type, int $parent_id): int
    {
        if (isset(self::$counts_by_parent[$parent_type][$parent_id])) {
            return (int) self::$counts_by_parent[$parent_type][$parent_id];
        } else {
            return (int) DB::executeFirstCell(
                'SELECT COUNT(id) AS "row_count" FROM comments WHERE parent_type = ? AND parent_id = ? AND is_trashed = ?',
                $parent_type,
                $parent_id,
                false
            );
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$counts_by_parent = [];
    }

    /**
     * Delete entries by parents.
     *
     * $parents is an array where key is parent type and value is array of
     * object ID-s of that particular parent
     *
     * @param array $parents
     */
    public static function deleteByParents($parents)
    {
        try {
            DB::beginWork('Removing comments by parent type and parent IDs @ ' . __CLASS__);

            if ($parents && is_foreachable($parents)) {
                foreach ($parents as $parent_type => $parent_ids) {
                    $rows = DB::execute('SELECT id, type FROM comments WHERE parent_type = ? AND parent_id IN (?)', $parent_type, $parent_ids);

                    if ($rows) {
                        $comments = [];

                        foreach ($rows as $row) {
                            if (array_key_exists($row['type'], $comments)) {
                                $comments[$row['type']][] = (int) $row['id'];
                            } else {
                                $comments[$row['type']] = [(int) $row['id']];
                            }
                        }

                        DB::execute('DELETE FROM comments WHERE parent_type = ? AND parent_id IN (?)', $parent_type, $parent_ids);

                        ActivityLogs::deleteByParents($comments);
                        Attachments::deleteByParents($comments);
                        ModificationLogs::deleteByParents($comments);
                    }
                }
            }

            DB::commit('Comments removed by parent type and parent IDs @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to remove comments by parent type and parent IDs @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Rebuild parent_path values based on creation info from comment parent objects.
     *
     * @param string $parent_type
     */
    public static function rebuildCommentCreatedParentPathForParentType($parent_type)
    {
        $escaped_parent_type = DB::escape($parent_type);

        try {
            DB::beginWork('Begin: rebuild comment creation logs based on parent creation data @ ' . __CLASS__);

            if ($objects_with_comments = DB::execute("SELECT parent_id, parent_path FROM activity_logs WHERE type = 'InstanceCreatedActivityLog' AND parent_type = $escaped_parent_type AND parent_id IN (SELECT DISTINCT parent_id FROM activity_logs WHERE type = 'CommentCreatedActivityLog' AND parent_type = $escaped_parent_type)")) {
                foreach ($objects_with_comments as $object_with_comments) {
                    DB::execute("UPDATE activity_logs SET parent_path = ? WHERE type = 'CommentCreatedActivityLog' AND parent_type = $escaped_parent_type AND parent_id = ?", $object_with_comments['parent_path'], $object_with_comments['parent_id']);
                }
            }

            DB::execute("DELETE FROM activity_logs WHERE type = 'CommentCreatedActivityLog' AND parent_type = $escaped_parent_type AND parent_path = ''"); // Remove comment entries for non-existing parent objects

            DB::commit('Done: rebuild comment creation logs based on parent creation data @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: rebuild comment creation logs based on parent creation data @ ' . __CLASS__);
            throw $e;
        }
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Comment
    {
        $comment = parent::create($attributes, $save, false);

        if ($comment->isLoaded() && $announce) {
            DataObjectPool::announce(new CommentCreatedEvent($comment));
        }

        return $comment;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Comment
    {
        $comment = parent::update($instance, $attributes, $save);

        if ($save) {
            DataObjectPool::announce(new CommentUpdatedEvent($comment));
        }

        return $comment;
    }
}
