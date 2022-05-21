<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level modification log management implementation.
 *
 * @package angie.frameworks.history
 * @subpackage models
 */
abstract class FwModificationLogs extends BaseModificationLogs
{
    /**
     * Return log entires by parent.
     *
     * @param  ApplicationObject|IHistory $parent
     * @return DBResult
     */
    public static function findByParent(IHistory $parent)
    {
        return ModificationLogs::find([
            'conditions' => ModificationLogs::parentToCondition($parent),
            'order' => 'created_on DESC',
        ]);
    }

    /**
     * Remove by parent.
     *
     * @throws Exception
     */
    public static function deleteByParent(IHistory $parent)
    {
        if ($log_ids = DB::executeFirstColumn('SELECT id FROM modification_logs WHERE parent_type = ? AND parent_id = ?', get_class($parent), $parent->getId())) {
            try {
                DB::beginWork('Removing modification log entries @ ' . __CLASS__);

                DB::execute('DELETE FROM modification_logs WHERE id IN (?)', $log_ids);
                DB::execute('DELETE FROM modification_log_values WHERE modification_id IN (?)', $log_ids);

                DB::commit('Modification log entries removed @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to remove modification log entries @ ' . __CLASS__);
                throw $e;
            }
        }
    }

    /**
     * Delete entries by parents.
     *
     * $parents is an array where key is parent type and value is array of
     * object ID-s of that particular parent
     *
     * @param  array     $parents
     * @throws Exception
     */
    public static function deleteByParents($parents)
    {
        if ($parents && is_foreachable($parents)) {
            $log_ids = [];

            foreach ($parents as $parent_type => $parent_ids) {
                if ($parent_log_ids = DB::executeFirstColumn('SELECT id FROM modification_logs WHERE parent_type = ? AND parent_id IN (?)', $parent_type, $parent_ids)) {
                    $log_ids = array_merge($log_ids, $parent_log_ids);
                }
            }

            if (count($log_ids)) {
                DB::transact(function () use ($log_ids) {
                    DB::execute('DELETE FROM modification_logs WHERE id IN (?)', $log_ids);
                    DB::execute('DELETE FROM modification_log_values WHERE modification_id IN (?)', $log_ids);
                }, 'Remove modification log entries');
            }
        }
    }
}
