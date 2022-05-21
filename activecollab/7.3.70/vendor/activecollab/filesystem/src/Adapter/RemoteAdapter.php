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

use Ssh\Session;

/**
 * @package ActiveCollab\FileSystem\Adapter
 */
class RemoteAdapter extends Adapter
{
    /**
     * @var Session
     */
    private $ssh_session;

    /**
     * @param Session $ssh_session
     * @param string  $sandbox_path
     */
    public function __construct(Session $ssh_session, $sandbox_path)
    {
        $this->ssh_session = $ssh_session;
        $this->setSandboxPath($sandbox_path);
    }

    /**
     * List all files that are in the given path.
     *
     * @param  string $path
     * @param  bool   $include_hidden
     * @return array
     */
    public function files($path = '/', $include_hidden = true)
    {
    }

    /**
     * List all subdirs that are in the given path.
     *
     * @param  string $path
     * @return array
     */
    public function subdirs($path = '/')
    {
    }

    /**
     * Create a link between $source and $target.
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string $source
     * @param string $target
     */
    public function link($source, $target)
    {
    }

    /**
     * Create a new file with the given data and optionally chmod it.
     *
     * @param string   $path
     * @param string   $data
     * @param int|null $mode
     */
    public function createFile($path, $data, $mode = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function readFile($path)
    {
    }

    /**
     * Write to a file. If file does not exist it will be created.
     *
     * @param string   $path
     * @param string   $data
     * @param int|null $mode
     */
    public function writeFile($path, $data, $mode = null)
    {
    }

    /**
     * Replace values in a text file.
     *
     * @param string $path
     * @param array  $search_and_replace
     */
    public function replaceInFile($path, array $search_and_replace)
    {
    }

    /**
     * Copy $source file to $target.
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string   $source
     * @param string   $target
     * @param int|null $mode
     */
    public function copyFile($source, $target, $mode = null)
    {
    }

    /**
     * Create a new directory.
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $recursive
     * @return bool
     */
    public function createDir($path, $mode = 0777, $recursive = true)
    {
    }

    /**
     * Copy a directory content from $source to $target.
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string     $source
     * @param string     $target
     * @param bool|false $empty_target
     */
    public function copyDir($source, $target, $empty_target = false)
    {
    }

    /**
     * Remove a directory.
     *
     * @param string $path
     * @param array  $exclude
     */
    public function emptyDir($path = '/', array $exclude = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path = '/', $check_path_exists = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path = '/', $check_path_exists = false)
    {
    }

    /**
     * Return full path from sanbox path and $path.
     *
     * @param  string $path
     * @return string
     */
    public function getFullPath($path = '/')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function changePermissions($path, $mode = 0777, $recursive = false)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isDir($path = '/')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isFile($path = '/')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isLink($path = '/')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function compress($path, array $files)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uncompress($path, $extract_to)
    {
    }
}
