<?php

/*
 * This file is part of the Active Collab File System.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\FileSystem;

use ActiveCollab\FileSystem\Adapter\AdapterInterface;

/**
 * @package ActiveCollab\Filesystem
 */
class FileSystem implements FileSystemInterface
{
    /**
     * @var \ActiveCollab\FileSystem\Adapter\AdapterInterface
     */
    private $adapter;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Return user Adapter instance.
     *
     * @return \ActiveCollab\FileSystem\Adapter\AdapterInterface
     */
    public function &getAdapter()
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getSandboxPath()
    {
        return $this->adapter->getSandboxPath();
    }

    /**
     * {@inheritdoc}
     */
    public function &setSandboxPath($sandbox_path)
    {
        $this->adapter->setSandboxPath($sandbox_path);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function files($path = '/', $include_hidden = true)
    {
        return $this->adapter->files($path, $include_hidden);
    }

    /**
     * {@inheritdoc}
     */
    public function subdirs($path = '/')
    {
        return $this->adapter->subdirs($path);
    }

    /**
     * {@inheritdoc}
     */
    public function link($source, $target)
    {
        $this->adapter->link($source, $target);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile($path, $data, $mode = null)
    {
        $this->adapter->createFile($path, $data, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function readFile($path)
    {
        return $this->adapter->readFile($path);
    }

    /**
     * {@inheritdoc}
     */
    public function writeFile($path, $data, $mode = null)
    {
        $this->adapter->writeFile($path, $data, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceInFile($path, array $search_and_replace)
    {
        $this->adapter->replaceInFile($path, $search_and_replace);
    }

    /**
     * {@inheritdoc}
     */
    public function renameFile($path, $new_name)
    {
        $this->adapter->renameFile($path, $new_name);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFile($source, $target, $mode = null)
    {
        $this->adapter->copyFile($source, $target, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, $mode = 0777, $recursive = true)
    {
        return $this->adapter->createDir($path, $mode, $recursive);
    }

    /**
     * {@inheritdoc}
     */
    public function renameDir($path, $new_name)
    {
        $this->adapter->renameDir($path, $new_name);
    }

    /**
     * {@inheritdoc}
     */
    public function copyDir($source, $target, $empty_target = false)
    {
        $this->adapter->copyDir($source, $target, $empty_target);
    }

    /**
     * {@inheritdoc}
     */
    public function emptyDir($path = '/', array $exclude = [])
    {
        $this->adapter->emptyDir($path, $exclude);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path = '/', $check_path_exists = false)
    {
        return $this->adapter->delete($path, $check_path_exists);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path = '/', $check_path_exists = false)
    {
        $this->adapter->deleteDir($path, $check_path_exists);
    }

    /**
     * {@inheritdoc}
     */
    public function getFullPath($path = '/')
    {
        return $this->adapter->getFullPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function changePermissions($path, $mode = 0777, $recursive = false)
    {
        return $this->adapter->changePermissions($path, $mode, $recursive);
    }

    /**
     * {@inheritdoc}
     */
    public function isDir($path = '/')
    {
        return $this->adapter->isDir($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isFile($path = '/')
    {
        return $this->adapter->isFile($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isLink($path = '/')
    {
        return $this->adapter->isLink($path);
    }

    /**
     * {@inheritdoc}
     */
    public function compress($path, array $files)
    {
        return $this->adapter->compress($path, $files);
    }

    /**
     * {@inheritdoc}
     */
    public function uncompress($path, $extract_to)
    {
        return $this->adapter->uncompress($path, $extract_to);
    }
}
