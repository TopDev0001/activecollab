<?php

/*
 * This file is part of the Active Collab File System.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\FileSystem\Adapter;

/**
 * @package ActiveCollab\FileSystem\Adapter
 */
interface AdapterInterface
{
    /**
     * Return sandbox path.
     *
     * @return string
     */
    public function getSandboxPath();

    /**
     * Set sandbox path.
     *
     * @param  string $sandbox_path
     * @return $this
     */
    public function &setSandboxPath($sandbox_path);

    /**
     * List all files that are in the given path.
     *
     * @param  string $path
     * @param  bool   $include_hidden
     * @return array
     */
    public function files($path = '/', $include_hidden = true);

    /**
     * List all subdirs that are in the given path.
     *
     * @param  string $path
     * @return array
     */
    public function subdirs($path = '/');

    /**
     * Create a link between $source and $target.
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string $source
     * @param string $target
     */
    public function link($source, $target);

    /**
     * Create a new file with the given data and optionally chmod it.
     *
     * @param string   $path
     * @param string   $data
     * @param int|null $mode
     */
    public function createFile($path, $data, $mode = null);

    /**
     * Return file contents.
     *
     * @param  string $path
     * @return string
     */
    public function readFile($path);

    /**
     * Write to a file. If file does not exist it will be created.
     *
     * @param string   $path
     * @param string   $data
     * @param int|null $mode
     */
    public function writeFile($path, $data, $mode = null);

    /**
     * Replace values in a text file.
     *
     * @param string $path
     * @param array  $search_and_replace
     */
    public function replaceInFile($path, array $search_and_replace);

    /**
     * Rename a file.
     *
     * Rename is done in the same directory. It can't be used to move a file to a different directory.
     *
     * @param  string $path
     * @param  string $new_name
     * @return bool
     */
    public function renameFile($path, $new_name);

    /**
     * Copy $source file to $target.
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string   $source
     * @param string   $target
     * @param int|null $mode
     */
    public function copyFile($source, $target, $mode = null);

    /**
     * Create a new directory.
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $recursive
     * @return bool
     */
    public function createDir($path, $mode = 0777, $recursive = true);

    /**
     * Rename a directory.
     *
     * Rename is done in the same directory. It can't be used to move a directory.
     *
     * @param  string $path
     * @param  string $new_name
     * @return bool
     */
    public function renameDir($path, $new_name);

    /**
     * Copy a directory content from $source to $target.
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string     $source
     * @param string     $target
     * @param bool|false $empty_target
     */
    public function copyDir($source, $target, $empty_target = false);

    /**
     * Remove a directory.
     *
     * @param string $path
     * @param array  $exclude
     */
    public function emptyDir($path = '/', array $exclude = []);

    /**
     * Remove a file.
     *
     * @param string $path
     * @param bool   $check_path_exists
     */
    public function delete($path = '/', $check_path_exists = false);

    /**
     * Remove a directory.
     *
     * @param string $path
     * @param bool   $check_path_exists
     */
    public function deleteDir($path = '/', $check_path_exists = false);

    /**
     * Return full path from sanbox path and $path.
     *
     * @param  string $path
     * @return string
     */
    public function getFullPath($path = '/');

    /**
     * Attempts to change the mode of the specified file to that given in mode.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive = false
     */
    public function changePermissions($path, $mode = 0777, $recursive = false);

    /**
     * Returns TRUE if the filename exists and is a directory, FALSE otherwise.
     *
     * @param  string $path
     * @return bool
     */
    public function isDir($path = '/');

    /**
     * Tells whether the given file is a regular file.
     *
     * @param  string $path
     * @return bool
     */
    public function isFile($path = '/');

    /**
     * Tells whether the given file is a symbolic link.
     *
     * @param  string $path
     * @return bool
     */
    public function isLink($path = '/');

    /**
     * Compress content to an archive.
     *
     * @param string $path
     * @param array  $files
     */
    public function compress($path, array $files);

    /**
     * Extract an archive to a given location.
     *
     * @param string $path
     * @param string $extract_to
     */
    public function uncompress($path, $extract_to);
}
