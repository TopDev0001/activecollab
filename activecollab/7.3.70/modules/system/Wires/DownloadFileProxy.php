<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Wires;

use ActiveCollab\Module\System\Wires\Base\FileProxy;
use ActiveCollab\Warehouse\Api\UrlCreator;

class DownloadFileProxy extends FileProxy
{
    /**
     * @var string
     */
    private $context;

    /**
     * Id of attachment were going to download.
     *
     * @var int
     */
    private $id;

    /**
     * File size of the download.
     *
     * @var int
     */
    private $size;

    /**
     * hash of the file.
     *
     * @var string
     */
    private $md5;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * Force download.
     *
     * @var bool
     */
    private $force;

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
        $this->force = isset($params['force']) && $params['force'];
        $this->authorized_at = isset($params['authorized_at']) && $params['authorized_at'] ? (int) $params['authorized_at'] : null;
    }

    /**
     * Forward image.
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

        if ($file['type'] !== 'WarehouseAttachment' && $file['type'] !== 'WarehouseFile') {
            $this->verifyAccessPermission(
                'download_file',
                $file['location'],
                $this->md5,
                $this->authorized_at,
                $this->force
            );
        }

        $file_path = UPLOAD_PATH . '/' . $file['location'];

        if (is_file($file_path)) {
            $mime_type = isset($file['mime_type']) && $file['mime_type']
                ? $file['mime_type']
                : 'application/octet-stream';

            header('Content-type: ' . $mime_type);
            header('Cache-Control: public, max-age=315360000');
            header('Pragma: public');
            header('Etag: ' . $this->md5);

            $cached_hash = $this->getCachedEtag();

            if ($cached_hash && $cached_hash == $this->md5) {
                $this->notModified();
            }

            download_file($file_path, $mime_type, $file['name'], $this->force, true);
        } elseif ($file['type'] === 'WarehouseAttachment' || $file['type'] === 'WarehouseFile') {
            $download_url = $this->getWarehouseDownloadUrl($file['location'], $this->md5);

            if ($download_url) {
                $this->redirect($download_url, true);
            } else {
                $this->notFound();
            }
        } else {
            $this->notFound();
        }
    }

    /**
     * @param  string $file_path
     * @return string
     */
    protected function getHumanType($file_path)
    {
        $mime_type = mime_content_type($file_path);

        $human_type = 'unknown';
        if (!empty(preg_match('#^image/(.*)$#', $mime_type))) {
            $human_type = 'image';
        } elseif ('application/pdf' === $mime_type) {
            $human_type = 'document';
        }

        return $human_type;
    }

    public function getWarehouseDownloadUrl($location, $md5)
    {
        $url_creator_path = APPLICATION_PATH . '/vendor/activecollab/warehouse-client/src/ActiveCollab/Warehouse/Api/UrlCreator.php';

        if (is_file($url_creator_path)) {
            require_once $url_creator_path;

            $url_creator = new UrlCreator(WAREHOUSE_URL);

            return $url_creator->getForceDownloadUrl($location, $md5);
        }

        return '';
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
