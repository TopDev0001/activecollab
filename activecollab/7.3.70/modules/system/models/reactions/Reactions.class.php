<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Reactions class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class Reactions extends BaseReactions
{
    /**
     * @var array
     */
    private static $details_by_parent = [];

    /**
     * Preload reactions details for a given type and a list of ID-s.
     *
     * @param string $parent_type
     */
    public static function preloadDetailsByParents($parent_type, array $parent_ids)
    {
        self::$details_by_parent[$parent_type] = [];

        if ($rows = DB::execute('SELECT id, type, parent_id, created_on, created_by_id, created_by_name, created_by_email FROM reactions WHERE parent_type = ? AND parent_id IN (?) ORDER BY created_on', $parent_type, $parent_ids)) {
            $rows->setCasting([
                'created_on' => DBResult::CAST_DATETIME,
            ]);

            foreach ($rows as $row) {
                if (empty(self::$details_by_parent[$parent_type][$row['parent_id']])) {
                    self::$details_by_parent[$parent_type][$row['parent_id']] = [];
                }

                $data = [
                    'id' => $row['id'],
                    'class' => $row['type'],
                    'parent_type' => $parent_type,
                    'parent_id' => $row['parent_id'],
                    'created_on' => $row['created_on'],
                    'created_by_id' => $row['created_by_id'],
                    'created_by_name' => $row['created_by_name'],
                    'created_by_email' => $row['created_by_email'],
                ];

                self::$details_by_parent[$parent_type][$row['parent_id']][$row['id']] = $data;
            }
        }

        if ($zeros = array_diff($parent_ids, array_keys(self::$details_by_parent[$parent_type]))) {
            foreach ($zeros as $parent_with_no_labels) {
                self::$details_by_parent[$parent_type][$parent_with_no_labels] = [];
            }
        }
    }

    /**
     * @param  DataObject|IReactions $parent
     * @return array
     */
    public static function getDetailsByParent(IReactions $parent)
    {
        $parent_type = get_class($parent);
        $parent_id = $parent->getId();

        if (isset(self::$details_by_parent[$parent_type][$parent_id])) {
            return array_values(self::$details_by_parent[$parent_type][$parent_id]);
        } else {
            $result = [];

            if ($rows = DB::execute('SELECT id, type, created_on, created_by_id, created_by_name, created_by_email FROM reactions WHERE parent_type = ? AND parent_id IN (?) ORDER BY created_on', $parent_type, $parent_id)) {
                $rows->setCasting([
                    'created_on' => DBResult::CAST_DATETIME,
                ]);

                foreach ($rows as $row) {
                    $result[] = [
                        'id' => $row['id'],
                        'class' => $row['type'],
                        'parent_type' => $parent_type,
                        'parent_id' => $parent_id,
                        'created_on' => $row['created_on'],
                        'created_by_id' => $row['created_by_id'],
                        'created_by_name' => $row['created_by_name'],
                        'created_by_email' => $row['created_by_email'],
                    ];
                }
            }

            return $result;
        }
    }

    public static function getAvailableTypes(): array
    {
        return [
            ['type' => ThumbsUpReaction::class, 'short_name' => 'thumbs_up'],
            ['type' => SmileReaction::class, 'short_name' => 'smile'],
            ['type' => ApplauseReaction::class, 'short_name' => 'applause'],
            ['type' => HeartReaction::class, 'short_name' => 'heart'],
            ['type' => PartyReaction::class, 'short_name' => 'party'],
            ['type' => ThinkingReaction::class, 'short_name' => 'thinking'],
            ['type' => ThumbsDownReaction::class, 'short_name' => 'thumbs_down'],
        ];
    }
}
