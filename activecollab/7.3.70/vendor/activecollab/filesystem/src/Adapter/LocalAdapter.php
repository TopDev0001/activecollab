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
use RuntimeException;

/**
 * @package ActiveCollab\FileSystem\Adapter
 */
class LocalAdapter extends Adapter
{
    /**
     * @var string
     */
    protected $compress_cmd = 'tar -jcf ';

    /**
     * @var string
     */
    protected $un_compress_cmd = 'tar -jxf ';

    /**
     * @param string|null $sandbox_path
     */
    public function __construct($sandbox_path)
    {
        $this->setSandboxPath($sandbox_path);
    }

    /**
     * {@inheritdoc}
     */
    public function files($path = '/', $include_hidden = true)
    {
        $dir_path = $this->withSlash($this->getFullPath($path));

        if (is_dir($dir_path)) {
            $files = $this->filesWithFullPaths($dir_path, $include_hidden);

            if (count($files)) {
                foreach ($files as $k => $path) {
                    $files[$k] = mb_substr($path, $this->getSandboxPathLength());
                }
            }

            sort($files);

            return $files;
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * Return a list of files from a directory.
     *
     * This function ignores hidden folders!
     *
     * @param  string $dir
     * @param  bool   $include_hidden
     * @param  bool   $recursive
     * @return array
     */
    private function filesWithFullPaths($dir, $include_hidden = true, $recursive = false)
    {
        $dir = $this->withSlash($dir);

        $result = [];

        if ($dirstream = opendir($dir)) {
            while (false !== ($filename = readdir($dirstream))) {
                $path = $dir . $filename;

                if ($filename != '.' && $filename != '..') {
                    if (is_dir($path)) {
                        if ($recursive) {
                            $files_from_subdir = $this->filesWithFullPaths($path, $recursive);

                            if (is_array($files_from_subdir)) {
                                $result = array_merge($result, $files_from_subdir);
                            }
                        }
                    } else {
                        if (is_file($path) || (is_link($path) && is_file(readlink($path)))) {
                            if (mb_substr($filename, 0, 1) == '.' && !$include_hidden) {
                                continue;
                            }

                            $result[] = $path;
                        }
                    }
                }
            }
        }

        closedir($dirstream);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function subdirs($path = '/')
    {
        $dir_path = $this->withSlash($this->getFullPath($path));

        if (is_dir($dir_path)) {
            $subdirs = $this->subdirsWithFullPaths($dir_path);

            if (count($subdirs)) {
                foreach ($subdirs as $k => $path) {
                    $subdirs[$k] = mb_substr($path, $this->getSandboxPathLength());
                }
            }

            sort($subdirs);

            return $subdirs;
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * {@inheritdoc}
     */
    private function subdirsWithFullPaths($dir, $recursive = false)
    {
        $dir = $this->withSlash($dir);

        $result = [];

        if ($dirstream = opendir($dir)) {
            while (false !== ($filename = readdir($dirstream))) {
                $path = $dir . $filename;
                if ($filename != '.' && $filename != '..' && is_dir($path)) {
                    $result[] = $path;

                    if ($recursive) {
                        $sub_dirs = $this->subdirsWithFullPaths($path, $recursive);
                        if (is_array($sub_dirs)) {
                            $result = array_merge($result, $sub_dirs);
                        }
                    }
                }
            }
        }

        closedir($dirstream);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function link($source, $target)
    {
        $target_path = $this->getFullPath($target);

        if (file_exists($target_path)) {
            throw new InvalidArgumentException("$target already exists");
        }

        if (!symlink($source, $target_path)) {
            throw new RuntimeException("Failed to link $source to $target");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createFile($path, $data, $mode = null)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            throw new InvalidArgumentException("File $path already exists");
        }

        if (file_put_contents($file_path, $data)) {
            if ($mode !== null) {
                $old_umask = umask(0);
                chmod($file_path, $mode);
                umask($old_umask);
            }
        } else {
            throw new RuntimeException("Failed to write to $path");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readFile($path)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            if (is_readable($file_path)) {
                $content = file_get_contents($file_path);

                if ($content !== false) {
                    return $content;
                } else {
                    throw new RuntimeException("Failed to read $path file");
                }
            } else {
                throw new RuntimeException("File $path is not readable");
            }
        } else {
            throw new InvalidArgumentException("File $path does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeFile($path, $data, $mode = null)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            if (file_put_contents($file_path, $data)) {
                if ($mode !== null) {
                    $old_umask = umask(0);
                    chmod($file_path, $mode);
                    umask($old_umask);
                }
            } else {
                throw new RuntimeException("Failed to write to $path");
            }
        } else {
            $this->createFile($path, $data, $mode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function replaceInFile($path, array $search_and_replace)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            if (!file_put_contents($file_path,
                str_replace(array_keys($search_and_replace), $search_and_replace, file_get_contents($file_path)))
            ) {
                throw new RuntimeException("Failed to write to $path");
            }
        } else {
            throw new InvalidArgumentException("File $path does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renameFile($path, $new_name)
    {
        if (empty($new_name)) {
            throw new InvalidArgumentException('New file name is required');
        }

        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            $new_file_path = dirname($file_path) . '/' . $new_name;

            if (file_exists($new_file_path)) {
                throw new RuntimeException("Failed to rename $path to $new_name, $new_name already exists");
            }

            if (dirname($file_path) != dirname($new_file_path)) {
                throw new RuntimeException("Rename option can't be used to move file to a different directory");
            }

            if (!rename($file_path, $new_file_path)) {
                throw new RuntimeException("Failed to rename $path to $new_name");
            }
        } else {
            throw new InvalidArgumentException("File $path does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copyFile($source, $target, $mode = null)
    {
        $target_path = $this->getFullPath($target);

        if (file_exists($target_path)) {
            throw new InvalidArgumentException("$target already exists");
        }

        if (copy($source, $target_path)) {
            if ($mode !== null) {
                $old_umask = umask(0);
                chmod($target_path, $mode);
                umask($old_umask);
            }
        } else {
            throw new RuntimeException("Failed to link $source to $target");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, $mode = 0777, $recursive = true)
    {
        $dir_path = $this->getFullPath($path);

        if (!is_dir($dir_path)) {
            $old_umask = umask(0);
            $dir_created = mkdir($dir_path, $mode, $recursive);
            umask($old_umask);

            return $dir_created;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function renameDir($path, $new_name)
    {
        if (empty($new_name)) {
            throw new InvalidArgumentException('New directory name is required');
        }

        $dir_path = $this->getFullPath($path);

        if (is_dir($dir_path)) {
            $new_dir_path = dirname($dir_path) . '/' . $new_name;

            if (file_exists($new_dir_path)) {
                throw new RuntimeException("Failed to rename $path to $new_name, $new_name exists");
            }

            if (dirname($dir_path) != dirname($new_dir_path)) {
                throw new RuntimeException("Rename option can't be used to move a directory to a different directory");
            }

            if (!rename($dir_path, $new_dir_path)) {
                throw new RuntimeException("Failed to rename $path to $new_name, operation failed");
            }
        } else {
            throw new InvalidArgumentException("Directory $path does not exist");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copyDir($source, $target, $empty_target = false)
    {
        $source = $this->withSlash($source);

        if (!is_dir($source)) {
            throw new InvalidArgumentException("Source path $source is not a directory");
        }

        $target_path = $this->getFullPath($target);

        if (is_dir($target_path)) {
            if ($empty_target) {
                $this->emptyDir($target);
            }
        } else {
            $this->createDir($target);
        }

        if ($dir_handle = dir($source)) {
            $target = $this->withSlash($target);

            while (false !== ($entry = $dir_handle->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                if (is_link("{$source}{$entry}")) {
                    $this->link(readlink("{$source}{$entry}"), "{$target}{$entry}");
                } else {
                    if (is_dir("{$source}{$entry}")) {
                        $this->copyDir("{$source}{$entry}", "{$target}{$entry}");
                    } else {
                        if (is_file("{$source}{$entry}")) {
                            $this->copyFile("{$source}{$entry}", "{$target}{$entry}", 0777);
                        }
                    }
                }
            }

            $dir_handle->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function emptyDir($path = '/', array $exclude = [])
    {
        $dir_path = $this->getFullPath($path);

        if (is_dir($dir_path)) {
            if (count($exclude)) {
                $exclude_path_prefix = rtrim($path, '/');

                foreach ($exclude as $k => $v) {
                    $exclude[$k] = $this->getFullPath($exclude_path_prefix . '/' . ltrim($v, '/'));
                }
            }

            $this->deleteDirByFullPath($dir_path, false, $exclude);
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path = '/', $check_path_exists = false)
    {
        $full_path = $this->getFullPath($path);

        if (is_link($full_path) && is_file(readlink($full_path))) {
            unlink($full_path);
        } elseif (!is_link($full_path) && is_file($full_path)) {
            unlink($full_path);
        } elseif (is_dir($full_path) || (is_link($full_path) && is_dir(readlink($full_path)))) {
            throw new InvalidArgumentException('$path is not a directory (or link to a directory)');
        } elseif ($check_path_exists) {
            throw new InvalidArgumentException('$path is not a file (or link to a file)');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path = '/', $check_path_exists = false)
    {
        $dir_path = $this->getFullPath($path);

        if (is_dir($dir_path)) {
            $this->deleteDirByFullPath($dir_path);
        } elseif ($check_path_exists) {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * Delete directory by full path.
     *
     * @param string $path
     * @param bool   $delete_self
     * @param array  $exclude
     */
    private function deleteDirByFullPath($path, $delete_self = true, array $exclude = [])
    {
        $dir = $this->withSlash($path);

        if ($dh = opendir($dir)) {
            while ($file = readdir($dh)) {
                if (($file != '.') && ($file != '..')) {
                    $fullpath = $dir . $file;

                    if (!$delete_self && in_array($fullpath, $exclude)) {
                        continue;
                    }

                    if (is_link($fullpath)) {
                        unlink($fullpath);
                    } else {
                        if (is_dir($fullpath)) {
                            $this->deleteDirByFullPath($fullpath);
                        } else {
                            unlink($fullpath);
                        }
                    }
                }
            }

            closedir($dh);
        }

        if ($delete_self) {
            rmdir($dir);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function changePermissions($path, $mode = 0777, $recursive = false)
    {
        $old_umask = umask(0);

        if ($this->isDir($path) && $recursive) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getFullPath($path))) as $item) {
                chmod($item, $mode);
            }
        } else {
            chmod($this->getFullPath($path), $mode);
        }

        umask($old_umask);
    }

    /**
     * {@inheritdoc}
     */
    public function isDir($path = '/')
    {
        return is_dir($this->getFullPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function isFile($path = '/')
    {
        return is_file($this->getFullPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function isLink($path = '/')
    {
        return is_link($this->getFullPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function compress($path, array $files)
    {
        if (empty($files)) {
            $escaped_file_names = '';
        } else {
            $escaped_file_names = ' ' . implode(' ', array_map(function ($file) {
                if ($this->isFile($file) || $this->isDir($file)) {
                    return escapeshellarg($this->withoutStartSlash($file));
                } else {
                    throw new RuntimeException(sprintf("Invalid file path '$file'"));
                }
            }, $files));
        }

        $exec_code = 0;
        $exec_out = [];

        exec($this->compress_cmd . escapeshellarg($this->getFullPath($path)) .' -C '. $this->getFullPath('/') . $escaped_file_names, $exec_out, $exec_code);

        if ($exec_code !== 0) {
            throw new RuntimeException('Error on file tar compress.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uncompress($path, $extract_to)
    {
        if (!$this->isFile($path)) {
            throw new RuntimeException(sprintf("Invalid file path '$path'"));
        }

        $exec_code = 0;
        $exec_out = [];

        exec($this->un_compress_cmd . ' ' . escapeshellarg($this->getFullPath($path)) . ' -C ' . escapeshellarg($this->getFullPath($extract_to)), $exec_out, $exec_code);

        if ($exec_code !== 0) {
            throw new RuntimeException('Error on file tar uncompress');
        }
    }
}
