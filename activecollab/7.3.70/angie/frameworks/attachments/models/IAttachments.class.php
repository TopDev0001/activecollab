<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Definition of attachments interface.
 *
 * @package angie.framework.attatchments
 * @subpackage models
 */
interface IAttachments
{
    const INLINE = 'inline';
    const ATTACHMENT = 'attachment';

    /**
     * Return true if ths object has attachments.
     *
     * @return mixed
     */
    public function hasAttachments();

    /**
     * Return files that are attached to this object.
     *
     * @return mixed
     */
    public function getAttachments();

    /**
     * Return number of files attached to this object.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countAttachments($use_cache = true);

    /**
     * Return object's inline attachments (files attached to object's body text).
     *
     * @return Attachment[]
     */
    public function getInlineAttachments();

    /**
     * Attach file from file system.
     *
     * If $name and/or $type are missing they will be extracted from real file
     *
     * If $commit is TRUE, pending files will be commited
     *
     * @param  string     $path
     * @param  string     $filename
     * @param  string     $mime_type
     * @param  User       $user
     * @return Attachment
     */
    public function attachFile($path, $filename = null, $mime_type = 'application/octet-stream', $user = null);

    /**
     * Attach uploaded file.
     *
     * @param  string     $disposition
     * @return Attachment
     */
    public function attachUploadedFile(UploadedFile &$uploaded_file, $disposition = self::ATTACHMENT);

    /**
     * Attach files from array.
     *
     * $from keys are:
     *
     * - path
     * - filename
     * - type
     *
     * If $commit is TRUE, pending files will be commited to database
     *
     * @param  array $from
     * @return int
     */
    public function attachFilesFromArray($from);

    /**
     * Clone attachments to a given object.
     *
     * @param  ApplicationObject|IAttachments $to
     * @return mixed
     */
    public function cloneAttachmentsTo(IAttachments $to);

    /**
     * Return search item that needs to be updated if attachments change.
     *
     * @return \Angie\Search\SearchItem\SearchItemInterface|null
     */
    public function updateSearchItemOnAttachmentsChange();

    /**
     * Prepare attachments to be send via CLI.
     *
     * array(
     *  array(
     *    'path' => path_to_file/image.png',
     *    'filename' => 'image.png',
     *    'type' => get_mime_type(path_to_file)
     *  ),
     * )
     *
     * @param  bool  $serialize
     * @return array
     */
    public function prepareAttachmentsForCLI($serialize = true);

    /**
     * Return object ID.
     *
     * @return int
     */
    public function getId();
}
