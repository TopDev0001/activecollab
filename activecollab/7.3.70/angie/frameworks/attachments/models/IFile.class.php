<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Response\FileDownload\FileDownload;

/**
 * File interface.
 *
 * @package angie.frameworks.attachments
 * @subpackage models
 */
interface IFile
{
    const IMAGE = 'image';
    const PDF = 'pdf';
    const PSD = 'psd';
    const VIDEO = 'video';
    const AUDIO = 'audio';
    const FLASH = 'flash';
    const OTHER = 'other';

    /**
     * Return file name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set file name.
     *
     * @param  string $value
     * @return string
     */
    public function setName($value);

    /**
     * Return file size, in bytes.
     *
     * @return int
     */
    public function getSize();

    /**
     * Set file size, in bytes.
     *
     * @param  int $value
     * @return int
     */
    public function setSize($value);

    /**
     * Return name of the file in /upload folder.
     *
     * @return string
     */
    public function getLocation();

    /**
     * Set file location.
     *
     * @param  string $value
     * @return string
     */
    public function setLocation($value);

    /**
     * Return file MIME type.
     *
     * @return string
     */
    public function getMimeType();

    /**
     * Set file MIME type.
     *
     * @param  string $value
     * @return string
     */
    public function setMimeType($value);

    /**
     * Return file hash.
     *
     * @return string
     */
    public function getMd5();

    /**
     * Set file hash.
     *
     * @param  string $value
     * @return string
     */
    public function setMd5($value);

    /**
     * Return local disk path.
     *
     * @return string
     */
    public function getPath();

    /**
     * Return file meta information.
     *
     * @return []
     */
    public function getFileMeta();

    /**
     * Keep file on delete.
     *
     * @param bool $yes_or_no
     */
    public function keepFileOnDelete($yes_or_no);

    /**
     * Prepare FileDownload instance.
     *
     * @return FileDownload
     */
    public function prepareForDownload();

    /**
     * Return true if this file is a remote file.
     *
     * @return bool
     */
    public function isRemote();

    // ---------------------------------------------------
    //  Set file values
    // ---------------------------------------------------

    /**
     * Set downloadble content from existing file on disk.
     *
     * If $save is set to true, save() method of parent object will be called.
     * This function returns path of destination file on success
     *
     * @param  string        $path
     * @param  bool          $save
     * @return string
     * @throws FileDnxError
     * @throws FileCopyError
     */
    public function setContentFromFile($path, $save = true);

    /**
     * Set content from uploaded file.
     *
     * If $save is set to true, save() method of parent object will be called.
     * This function returns path of destination file on success
     *
     * @param  array             $file
     * @param  bool              $save
     * @return string
     * @throws UploadError
     * @throws FileCopyError
     * @throws InvalidParamError
     */
    public function setContentFromUploadedFile($file, $save = true);

    // ---------------------------------------------------
    //  URL-s
    // ---------------------------------------------------

    /**
     * Return download URL.
     *
     * @param  bool   $force
     * @return string
     */
    public function getDownloadUrl($force = false);

    /**
     * Return preview URL.
     *
     * @return string
     */
    public function getPreviewUrl();

    /**
     * Return public download URL.
     *
     * @param  bool   $force
     * @return string
     */
    public function getPublicDownloadUrl($force = false);

    /**
     * Return file thumbnail URL.
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return string
     */
    public function getThumbnailUrl($width = 80, $height = 80, $scale = Thumbnails::SCALE);
}
