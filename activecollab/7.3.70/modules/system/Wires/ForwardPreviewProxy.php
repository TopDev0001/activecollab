<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Wires;

use ActiveCollab\Module\System\Wires\Base\FileProxy;

class ForwardPreviewProxy extends FileProxy
{
    /**
     * @var string
     */
    private $context;

    /**
     * Id.
     *
     * @var string
     */
    protected $id;

    /**
     * md5.
     *
     * @var string
     */
    protected $md5;

    /**
     * Size of file.
     *
     * @var object
     */
    protected $size;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * @var int|null
     */
    private $authorized_at;

    /**
     * Construct proxy request handler.
     *
     * @param null $params
     */
    public function __construct($params = null)
    {
        parent::__construct($params['time'] ?? time(), isset($params['i']) && $params['i'] ? $params['i'] : null);

        $this->context = isset($params['context']) && $params['context'] ? $params['context'] : null;
        $this->id = isset($params['id']) && $params['id'] ? trim($params['id']) : null;
        $this->size = isset($params['size']) && $params['size'] ? (int) $params['size'] : null;
        $this->md5 = isset($params['md5']) && $params['md5'] ? $params['md5'] : null;
        $this->timestamp = isset($params['timestamp']) && $params['timestamp'] ? $params['timestamp'] : null;
        $this->authorized_at = isset($params['authorized_at']) && $params['authorized_at'] ? (int) $params['authorized_at'] : null;
    }

    /**
     * Forward thumbnail.
     */
    public function execute()
    {
        $file = parent::getFile(
            $this->context,
            $this->id,
            $this->size,
            $this->md5,
            $this->timestamp
        );

        $this->verifyAccessPermission(
            'forward_preview',
            $file['location'],
            $this->md5,
            $this->authorized_at
        );

        $file_path = UPLOAD_PATH . '/' . $file['location'];
        if (!is_file($file_path)) {
            $this->notFound();
        }

        // supposed path for preview if it differs of original file
        $generated_preview_path = THUMBNAILS_PATH . "/preview-{$this->context}-" . str_replace('/', '-', $file['location']);

        // determine the source type
        $source_type = $this->getSourceType($file_path, $file['name']);

        // if this is PSD
        if ($source_type == self::SOURCE_PSD) {
            if (!is_file($generated_preview_path)) {
                if (!$this->generateFromPsd($file_path, $generated_preview_path, 1800, 1800, self::SCALE)) {
                    $this->operationFailed();
                }
            }

            $file_to_serve = $generated_preview_path;
            $mime_type_to_serve = 'image/jpeg';

            // if image or pdf
        } elseif ($source_type == self::SOURCE_IMAGE || $source_type == self::SOURCE_PDF) {
            $file_to_serve = $file_path;
            $mime_type_to_serve = isset($file['mime_type']) && $file['mime_type'] ? $file['mime_type'] : 'application/octet-stream';

            // unknown source type
        } else {
            $file_to_serve = null;
            $mime_type_to_serve = null;
        }

        // send X-Type Header
        header('X-Type: ' . $this->getHumanType($source_type));

        // send X-Width & X-Height headers if image type
        if ($source_type == self::SOURCE_PSD || $source_type == self::SOURCE_IMAGE) {
            $dimensions = getimagesize($file_path);

            /*
             * NOTE:
             * Values of these ternary expressions are assigned to a variable because of the issue we were having on this task:
             * https://app.activecollab.com/1/my-work?modal=Task-49504-204
             *
             * Using isset() function caused some issues when used inside header() function.
             * It might be a core PHP bug or something else,but it was reproducible only on FastCGI server for some reason.
             */
            $width = isset($dimensions[0]) ? $dimensions[0] : 0;
            $height = isset($dimensions[1]) ? $dimensions[1] : 0;

            header('X-Width: ' . $width);
            header('X-Height: ' . $height);
        }

        if (!$file_to_serve) {
            $this->unprocessableEntity();
        }

        // if this is HEAD response, we've done enough
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            header('Content-Type: ' . $mime_type_to_serve);
            $this->success();
        }

        header('Cache-Control: public, max-age=315360000');
        header('Pragma: public');
        header('Etag: ' . $this->md5);

        $cached_hash = $this->getCachedEtag();

        if ($cached_hash && $cached_hash == $this->md5) {
            $this->notModified();
        }

        download_file($file_to_serve, $mime_type_to_serve, $file['name'], false, true);
    }

    /**
     * @param  string $source_type
     * @return string
     */
    protected function getHumanType($source_type)
    {
        $human_type = 'unknown';

        if ($source_type == self::SOURCE_IMAGE || $source_type == self::SOURCE_PSD) {
            $human_type = 'image';
        } elseif ($source_type == self::SOURCE_PDF) {
            $human_type = 'document';
        }

        return $human_type;
    }

    /**
     * @param  string      $context
     * @return string|null
     */
    protected function contextToTableName($context)
    {
        if ($context === 'files') {
            return 'files';
        } elseif ($context === 'project_template_elements') {
            return 'project_template_elements';
        }

        return parent::contextToTableName($context);
    }
}
