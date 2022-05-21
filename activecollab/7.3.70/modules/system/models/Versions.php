<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class Versions.
 *
 * @package ActiveCollab.modules.system
 * @subpackage model
 */
final class Versions
{
    /**
     * @var string
     */
    private $root_path;

    /**
     * @param string|null $root_path
     */
    public function __construct($root_path = null)
    {
        $this->root_path = ROOT;

        if (!empty($root_path)) {
            $this->root_path = $root_path;
        }
    }

    /**
     * Scan activecollab root dir for all versions.
     *
     * @return array
     */
    public function scanVersionFolder()
    {
        $versions = [];
        $i = 0;
        $current_array_id = null;
        foreach (array_diff(scandir($this->root_path), ['.', '..']) as $folder) {
            if (is_dir($this->root_path . '/' . $folder)) {
                $versions[$i]['version'] = $folder;
                $versions[$i]['created_at'] = filectime($this->root_path . '/' . $folder);

                // Is folder writable
                if (folder_is_writable($this->root_path . '/' . $folder)) {
                    $versions[$i]['writable'] = true;
                } else {
                    $versions[$i]['writable'] = false;
                }

                // Check and set current version
                if ($folder == APPLICATION_VERSION) {
                    $versions[$i]['current'] = true;
                    $current_array_id = $i;
                } else {
                    $versions[$i]['current'] = false;
                }
            }
            ++$i;
        }

        $versions[] = $versions[$current_array_id];
        unset($versions[$current_array_id]);

        krsort($versions);
        $versions = array_values($versions);

        return $versions;
    }

    /**
     * Check if there old versions in ActiveCollab folder.
     *
     * @return array
     */
    public function checkOldVersions()
    {
        $versions = $this->scanVersionFolder();
        $old_versions = [];
        $havent_old_versions = true;
        $is_writable = true;
        $error_message = [];

        foreach ($versions as $version) {
            if (!$version['current']) {
                $old_versions[] = $version['version'];
                $havent_old_versions = false;
                if (!$version['writable']) {
                    $is_writable = false;
                }
            }
        }

        if (!$havent_old_versions) {
            $error_message[] = lang('Please remove unused old version directories and files') . '. ('.implode(', ', $old_versions) . ') ';
        }

        if (!$is_writable) {
            $error_message[] = lang('Old version directories are not writable. Please delete them manually.');
        }

        return [
            'versions_is_ok' => $havent_old_versions,
            'versions_errors' => $error_message,
            'is_writable' => $is_writable,
        ];
    }

    /**
     * Delete all old versions from ActiveCollab folder.
     *
     * @return array
     */
    public function deleteOldVersions()
    {
        $versions = $this->scanVersionFolder();

        foreach ($versions as $version) {
            if (!$version['current']) {
                $path = realpath($this->root_path . '/' .  $version['version']);
                if (is_dir($path)) {
                    delete_dir($path);
                }
            }
        }

        return $this->checkOldVersions();
    }
}
