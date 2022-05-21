<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Prepare model for avatars as uploaded files functionality.
 *
 * @package angie.migrations
 */
class MigrateModelForAvatarsAsUploadedFiles extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->useTableForAlter('uploaded_files')->addColumn(new DBIpAddressColumn('ip_address'), 'created_by_email');

        $users = $this->useTableForAlter('users');
        $users->addColumn(DBStringColumn::create('avatar_location', 255), 'password_reset_on');

        foreach (DB::executeFirstColumn('SELECT id FROM ' . $users->getName() . ' WHERE is_trashed = ?', false) as $user_id) {
            if ($location = $this->migrateExistingAvatar($user_id)) {
                DB::execute('UPDATE ' . $users->getName() . ' SET avatar_location = ? WHERE id = ?', $location, $user_id);
            }
        }
    }

    /**
     * Try to migrate existing avatar and return new location or FALSE on failure.
     *
     * @param  int         $user_id
     * @return string|bool
     */
    private function migrateExistingAvatar($user_id)
    {
        $expected_avatar_path = PUBLIC_PATH . "/avatars/$user_id.original.png";

        if (!is_file($expected_avatar_path)) {
            return false;
        }

        try {
            $location = AngieApplication::storeFile($expected_avatar_path)[1];

            if ($location) {
                @unlink($expected_avatar_path);

                foreach ([16, 40, 80, 256] as $size) {
                    $expected_avatar_path = PUBLIC_PATH . "/avatars/$user_id.{$size}x{$size}.png";

                    if (is_file($expected_avatar_path)) {
                        @unlink($expected_avatar_path);
                    }
                }
            }

            return $location;
        } catch (Exception $e) {
            return false;
        }
    }
}
