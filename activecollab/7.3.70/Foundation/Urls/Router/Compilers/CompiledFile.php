<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Compilers;

use FileDnxError;

class CompiledFile implements CompiledFileInterface
{
    private $content = '';

    public function writeLine(string $line = ''): void
    {
        $this->content .= $line . "\n";
    }

    public function save(string $file_path): bool
    {
        if (file_put_contents($file_path, $this->content)) {
            $this->applyDirPermissionsToFile($file_path);

            return true;
        }

        return false;
    }

    protected function applyDirPermissionsToFile(string $file_path): void
    {
        if (empty($file_path) || !file_exists($file_path)) {
            throw new FileDnxError($file_path);
        }

        if (DIRECTORY_SEPARATOR != '\\') {
            $file_stats = stat($file_path);
            $dir_stats = stat(dirname($file_path));

            if ($file_stats['gid'] != $dir_stats['gid']) {
                chgrp($file_path, $dir_stats['gid']);
            }

            if ($file_stats['uid'] != $dir_stats['uid']) {
                chown($file_path, $dir_stats['uid']);
            }
        }
    }
}
