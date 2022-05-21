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

use InvalidArgumentException;

/**
 * @package ActiveCollab\FileSystem\Adapter
 */
abstract class Adapter implements AdapterInterface
{
    /**
     * @var string
     */
    private $sandbox_path;

    /**
     * @var int
     */
    private $sandbox_path_length;

    /**
     * {@inheritdoc}
     */
    public function getSandboxPath()
    {
        return $this->sandbox_path;
    }

    /**
     * {@inheritdoc}
     */
    public function &setSandboxPath($sandbox_path)
    {
        $this->sandbox_path = $this->withSlash($sandbox_path);
        $this->sandbox_path_length = mb_strlen($this->sandbox_path);

        return $this;
    }

    /**
     * Return sandbox path length.
     *
     * @return int
     */
    public function getSandboxPathLength()
    {
        return $this->sandbox_path_length;
    }

    /**
     * Convert relative path to full path.
     *
     * @param  string                   $path
     * @return string
     * @throws InvalidArgumentException
     */
    public function getFullPath($path = '/')
    {
        if (mb_substr($path, 0, 1) == '/') {
            $path = mb_substr($path, 1);
        }

        $full_path = $this->getSandboxPath() . $path;

        if (strpos($full_path, '..')) {
            $full_path = realpath($full_path);

            if (mb_substr($full_path, 0, $this->sandbox_path_length) != $this->sandbox_path) {
                throw new InvalidArgumentException('$path is outside of the sanbox path');
            }
        }

        return $full_path;
    }

    /**
     * Return path with trailing slash.
     *
     * @param  string $path
     * @return string
     */
    protected function withSlash($path)
    {
        return substr($path, strlen($path) - 1) == '/' ? $path : $path . '/';
    }

    /**
     * Return path without start slash.
     *
     * @param  string $path
     * @return string
     */
    protected function withoutStartSlash($path)
    {
        return substr($path, 0, 1) == '/' ? substr($path, 1) : $path;
    }
}
