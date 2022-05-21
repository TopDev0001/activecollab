<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project label implementation.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class ProjectLabel extends Label implements ProjectLabelInterface
{
    /**
     * Remove this label from database.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting label @ ' . __CLASS__);

            /** @var User $logged_user */
            $logged_user = AngieApplication::authentication()->getAuthenticatedUser();

            DB::execute(
                'UPDATE projects
                    SET label_id = ?, updated_on = UTC_TIMESTAMP(), updated_by_id = ?, updated_by_name = ?, updated_by_email = ?
                    WHERE label_id = ?',
                0,
                $logged_user->getId(),
                $logged_user->getName(),
                $logged_user->getEmail(),
                $this->getId()
            );

            parent::delete($bulk);

            DB::commit('Label deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete label @ ' . __CLASS__);
            throw $e;
        }

        Projects::clearCache();
    }
}
