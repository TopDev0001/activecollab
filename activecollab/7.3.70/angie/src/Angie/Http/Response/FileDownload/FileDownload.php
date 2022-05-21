<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\Response\FileDownload;

use InvalidArgumentException;
use RuntimeException;

/**
 * File download response.
 *
 * @package Angie\Http
 */
class FileDownload implements FileDownloadInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $mime_type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $disposition;

    /**
     * @var bool
     */
    private $delete_source_when_done;

    /**
     * Construct the new instance.
     *
     * @param string $path
     * @param string $mime_type
     * @param string $name
     * @param string $disposition
     * @param bool   $delete_source_when_done
     */
    public function __construct($path, $mime_type = 'application/octet-stream', $name = null, $disposition = self::DOWNLOAD_ATTACHMENT, $delete_source_when_done = false)
    {
        if (!is_file($path)) {
            throw new RuntimeException('File not found');
        }

        if (!in_array($disposition, [self::DOWNLOAD_INLINE, self::DOWNLOAD_ATTACHMENT])) {
            throw new InvalidArgumentException('File disposition can be inline or attachment');
        }

        $this->path = $path;
        $this->mime_type = $mime_type;
        $this->name = $name ?: basename($this->path);
        $this->disposition = $disposition;
        $this->delete_source_when_done = (bool) $delete_source_when_done;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMimeType()
    {
        return $this->mime_type;
    }

    public function getDisposition()
    {
        return $this->disposition;
    }

    public function getDeleteSourceWhenDone()
    {
        return $this->delete_source_when_done;
    }
}
