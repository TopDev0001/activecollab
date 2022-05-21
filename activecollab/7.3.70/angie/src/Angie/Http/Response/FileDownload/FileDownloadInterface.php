<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\Response\FileDownload;

/**
 * @package Angie\Http\Response\FileDownload
 */
interface FileDownloadInterface
{
    const DOWNLOAD_ATTACHMENT = 'attachment';
    const DOWNLOAD_INLINE = 'inline';

    /**
     * Return file path.
     *
     * @return string
     */
    public function getPath();

    /**
     * Return file name.
     *
     * @return string
     */
    public function getName();

    /**
     * Return MIME type.
     *
     * @return string
     */
    public function getMimeType();

    /**
     * Return disposition.
     *
     * @return string
     */
    public function getDisposition();

    /**
     * Return delete source when done.
     *
     * @return bool
     */
    public function getDeleteSourceWhenDone();
}
