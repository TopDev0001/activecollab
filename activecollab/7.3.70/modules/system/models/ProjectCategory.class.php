<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectCategory extends Category
{
    public function canEdit(User $user): bool
    {
        return $user->isPowerUser();
    }

    public function canDelete(User $user): bool
    {
        return $user->isPowerUser();
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Delete this object from database.
     *
     * @param  bool      $bulk
     * @throws Exception
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Removing project category @ ' . __CLASS__);

            /** @var Project[] $projects */
            if ($projects = Projects::find(['conditions' => ['category_id = ?', $this->getId()]])) {
                foreach ($projects as $project) {
                    $project->setCategoryId(0);
                    $project->save();
                }
            }

            parent::delete($bulk);

            DB::commit('Project category removed @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to remove project category @ ' . __CLASS__);

            throw $e;
        }
    }
}
