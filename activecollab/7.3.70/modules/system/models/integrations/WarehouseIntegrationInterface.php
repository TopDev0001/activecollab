<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

/**
 * Warehouse integrations class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
interface WarehouseIntegrationInterface extends IntegrationInterface
{
    /**
     * Return client id.
     *
     * @return string|null
     */
    public function getClientId();

    /**
     * Return secret.
     *
     * @return string
     */
    public function getSecret();

    /**
     * Return instance ID.
     *
     * @return string
     */
    public function getInstanceId();

    /**
     * Return access token.
     *
     * @return string|null
     */
    public function getAccessToken();

    /**
     * Return store id.
     *
     * @return int|null
     */
    public function getStoreId();

    /**
     * Return warehouse url creator.
     *
     * @return \ActiveCollab\Warehouse\Api\UrlCreator
     */
    public function urlCreator();

    /**
     * Return upload url.
     *
     * @return string
     */
    public function getUploadUrl();

    /**
     * Return new upload intent.
     *
     * @return string
     */
    public function getUploadIntent(User $user = null);

    /**
     * Prepare file thumbnail url.
     *
     * @param  string $location
     * @param  string $md5_hash
     * @param  int    $width
     * @param  int    $height
     * @return string
     */
    public function prepareFileThumbnailUrl($location, $md5_hash, $width = 60, $height = 60);

    /**
     * Prepare file preview url.
     *
     * @param  string $location
     * @param  string $md5_hash
     * @return string
     */
    public function prepareFilePreviewUrl($location, $md5_hash);

    /**
     * Prepare file download url.
     *
     * @param  string $location
     * @param  string $md5_hash
     * @param  bool   $force
     * @return string
     */
    public function prepareFileDownloadUrl($location, $md5_hash, $force = true);

    /**
     * Return validity of a given upload intent.
     *
     * @param  string $intent
     * @return bool
     */
    public function isUploadIntentValid($intent);

    /**
     * Return warehouse client.
     *
     * @return \ActiveCollab\Warehouse\Client
     */
    public function client();

    /**
     * Initialize.
     *
     * response example
     * {
     *     "token": "access_token_example",
     *     "store_id": 1
     * }
     */
    public function initialize();

    /**
     * Process pingback request.
     *
     * @param  array $data
     * @return array
     */
    public function onPingbackRequest($data);

    /**
     * Prepare instructions for upload.
     *
     * @return array
     */
    public function prepareForUpload();

    /**
     * Prepare for files archive.
     *
     * @return array
     */
    public function prepareForFilesArchive(IAttachments $parent);

    /**
     * @param  string $backup_archive_path
     * @param  string $pingback_url
     * @param  string $signature
     * @param  string $export_type
     * @return mixed
     */
    public function exportStore($backup_archive_path, $pingback_url, $signature, $export_type);

    /**
     * @param string $download_url
     * @param string $archive_size
     */
    public function onStoreExportPingback(IUser $recipient, array $export_memory, $download_url, $archive_size);

    /**
     * Generate default pingback URL.
     *
     * @return string
     */
    public function getDefaultPingbackUrl();

    /**
     * @return \ActiveCollab\Warehouse\Api\FileApi
     */
    public function getFileApi();
}
