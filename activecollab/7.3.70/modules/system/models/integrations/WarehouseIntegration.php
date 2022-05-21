<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\ShepherdAccountConfig\Utils\ShepherdAccountConfigInterface;
use ActiveCollab\Warehouse\Api\FileApi;
use ActiveCollab\Warehouse\Api\InitializationApi;
use ActiveCollab\Warehouse\Api\StoreApi;
use ActiveCollab\Warehouse\Api\UrlCreator;
use ActiveCollab\Warehouse\Client;

class WarehouseIntegration extends Integration implements WarehouseIntegrationInterface
{
    const WAREHOUSE_UPLOAD_INTENT_TTL = 3600;
    const WAREHOUSE_INTENTS_CACHE_KEY = 'warehouse_intents';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var UrlCreator
     */
    protected $url_creator;

    /**
     * @var ShepherdAccountConfigInterface
     */
    private $shepherd_account_config = null;

    /**
     * @var InitializationApi
     */
    private $initialization_api;

    public function setInitializationApi(InitializationApi $initialization_api)
    {
        $this->initialization_api = $initialization_api;
    }

    private function getInitializationApi(): InitializationApi
    {
        if (empty($this->initialization_api)) {
            $this->initialization_api = (new InitializationApi($this->client(), $this->urlCreator()));
        }

        return $this->initialization_api;
    }

    public function setShepherdAccountConfig(ShepherdAccountConfigInterface $shepherd_account_config)
    {
        $this->shepherd_account_config = $shepherd_account_config;
    }

    private function getShepherdAccountConfig(): ShepherdAccountConfigInterface
    {
        if (empty($this->shepherd_account_config)) {
            $this->shepherd_account_config = AngieApplication::shepherdAccountConfig();
        }

        return $this->shepherd_account_config;
    }

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return $this->getStoreId() && $this->getAccessToken();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Warehouse';
    }

    public function getShortName(): string
    {
        return 'warehouse';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('File storage and preview service.');
    }

    /**
     * Return client id.
     *
     * @return string|null
     */
    public function getClientId()
    {
        return defined('WAREHOUSE_CLIENT_ID') && AngieApplication::isOnDemand()
            ? WAREHOUSE_CLIENT_ID
            : $this->getAdditionalProperty('client_id');
    }

    /**
     * Set client id.
     *
     * @param  string      $client_id
     * @return string|null
     */
    public function setClientId($client_id)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('client_id', $client_id);
        }

        return self::getClientId();
    }

    /**
     * Return secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return defined('WAREHOUSE_SECRET') && AngieApplication::isOnDemand()
            ? WAREHOUSE_SECRET
            : $this->getAdditionalProperty('secret');
    }

    /**
     * Set secret.
     *
     * @param  string $secret
     * @return string
     */
    public function setSecret($secret)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('secret', $secret);
        }

        return self::getSecret();
    }

    /**
     * Return secret.
     *
     * @return string
     */
    public function getInstanceId()
    {
        return AngieApplication::getAccountId();
    }

    /**
     * Set secret.
     *
     * @param  int $id
     * @return int
     */
    public function setInstanceId($id)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('instance_id', $id);
        }

        return self::getInstanceId();
    }

    /**
     * Return access token.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->getAdditionalProperty('access_token');
    }

    /**
     * Set access token.
     *
     * @param  string $access_token
     * @return mixed
     */
    public function setAccessToken($access_token)
    {
        return $this->setAdditionalProperty('access_token', $access_token);
    }

    /**
     * Return store id.
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->getAdditionalProperty('store_id');
    }

    /**
     * Set store id.
     *
     * @param  int $store_id
     * @return int
     */
    public function setStoreId($store_id)
    {
        return $this->setAdditionalProperty('store_id', $store_id);
    }

    /**
     * Return warehouse url creator.
     *
     * @return UrlCreator
     */
    public function urlCreator()
    {
        if (!($this->url_creator instanceof UrlCreator)) {
            $this->url_creator = new UrlCreator($this->getWarehouseUrl());
        }

        return $this->url_creator;
    }

    public function getWarehouseUrl()
    {
        return WAREHOUSE_URL;
    }

    /**
     * Return upload url.
     *
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->isInUse() ? $this->urlCreator()->getUploadUrl($this->getStoreId()) : null;
    }

    /**
     * Return new upload intent.
     *
     * @return string
     */
    public function getUploadIntent(User $user = null)
    {
        $intent = sha1(
            uniqid(
                sprintf(
                    '%d_%d_',
                    $user
                        ? $user->getId()
                        : mt_rand(defined('PHP_INT_MIN') ? PHP_INT_MIN : 0, PHP_INT_MAX),
                    microtime()
                )
            )
        );
        $intents = AngieApplication::memories()->get(self::WAREHOUSE_INTENTS_CACHE_KEY, []);
        $intents[] = $intent;

        AngieApplication::memories()->set(self::WAREHOUSE_INTENTS_CACHE_KEY, $intents);

        return $intent;
    }

    /**
     * Prepare file thumbnail url.
     *
     * @param  string $location
     * @param  string $md5_hash
     * @param  int    $width
     * @param  int    $height
     * @return string
     */
    public function prepareFileThumbnailUrl($location, $md5_hash, $width = 60, $height = 60)
    {
        return $this->addTokenPlaceholderToUrl(
            $this->urlCreator()->getThumbnailUrl($location, $md5_hash, $width . '_' . $height),
            'intent=--THUMBNAIL-TOKEN--'
        );
    }

    /**
     * Prepare file preview url.
     *
     * @param  string $location
     * @param  string $md5_hash
     * @return string
     */
    public function prepareFilePreviewUrl($location, $md5_hash)
    {
        return $this->addTokenPlaceholderToUrl(
            $this->urlCreator()->getPreviewUrl($location, $md5_hash),
            'intent=--PREVIEW-TOKEN--'
        );
    }

    /**
     * Prepare file download url.
     *
     * @param  string $location
     * @param  string $md5_hash
     * @param  bool   $force
     * @return string
     */
    public function prepareFileDownloadUrl($location, $md5_hash, $force = true)
    {
        $url = $force
            ? $this->urlCreator()->getForceDownloadUrl($location, $md5_hash)
            : $this->urlCreator()->getDownloadUrl($location, $md5_hash);

        return $this->addTokenPlaceholderToUrl(
            $url,
            'intent=--DOWNLOAD-TOKEN--'
        );
    }

    /**
     * Return validity of a given upload intent.
     *
     * @param  string $intent
     * @return bool
     */
    public function isUploadIntentValid($intent)
    {
        $intents = AngieApplication::memories()->get(self::WAREHOUSE_INTENTS_CACHE_KEY, []);
        $key = array_search($intent, $intents);

        if ($key !== false) {
            unset($intents[$key]);
            AngieApplication::memories()->set(self::WAREHOUSE_INTENTS_CACHE_KEY, $intents);

            return true;
        }

        return false;
    }

    /**
     * Return warehouse client.
     *
     * @return Client
     */
    public function client()
    {
        if (!($this->client instanceof Client)) {
            $this->client = new Client($this->getAccessToken());
        }

        return $this->client;
    }

    /**
     * Initialize.
     *
     * response example
     * {
     *     "token": "access_token_example",
     *     "store_id": 1
     * }
     */
    public function initialize()
    {
        if ($this->getAccessToken()) {
            throw new RuntimeException('Access token is already initialized');
        }

        $response = $this->getInitializationApi()
            ->initialize($this->getClientId(), $this->getSecret(), $this->getInstanceId());

        $this->setAccessToken($response['token']);
        $this->setStoreId($response['store_id']);
        $this->save();

        $this->getShepherdAccountConfig()->setWarehouseStoreId(AngieApplication::getAccountId(), $response['store_id']);
    }

    /**
     * Process pingback request.
     *
     * @param  array $data
     * @return array
     */
    public function onPingbackRequest($data)
    {
        $uploaded_file = null;
        $file = isset($data['file']) ? $data['file'] : null;

        if ($file) {
            $additional_properties = ['share_hash' => $file['share_hash']];
            if (isset($file['tika_data'])) {
                $additional_properties = array_merge($additional_properties, ['tika_data' => $file['tika_data']]);
            }

            /** @var UploadedFile $uploaded_file */
            $uploaded_file = UploadedFiles::create([
                'type' => 'WarehouseUploadedFile',
                'name' => $file['original_name'],
                'mime_type' => $file['mime_type'],
                'size' => $file['size'],
                'location' => $file['location'],
                'md5' => $file['md5_hash'],
                'raw_additional_properties' => serialize($additional_properties),
            ]);
        }

        return ['payload' => $uploaded_file];
    }

    /**
     * Prepare instructions for upload.
     *
     * @return array
     */
    public function prepareForUpload()
    {
        $user = AngieApplication::authentication()->getLoggedUser();

        return [
            'upload_url' => $this->getUploadUrl(),
            'pingback_url' => null,
            'intent' => $this->isInUse() ? $this->getUploadIntent($user) : null,
        ];
    }

    /**
     * Prepare for files archive.
     *
     * @return array
     */
    public function prepareForFilesArchive(IAttachments $parent)
    {
        $locations = [];
        if ($attachments = $parent->getAttachments()) {
            /** @var Attachment $attachment */
            foreach ($attachments as $attachment) {
                $locations[] = $attachment->getLocation();
            }
        }

        $response = $this->getFileApi()->filesArchive($locations);

        return ['download_url' => $response['download_url']];
    }

    /**
     * @param  string $backup_archive_path
     * @param  string $pingback_url
     * @param  string $signature
     * @param  string $export_type
     * @return mixed
     */
    public function exportStore($backup_archive_path, $pingback_url, $signature, $export_type)
    {
        return (new StoreApi(
            $this->client(),
            $this->urlCreator()
        ))->export($this->getStoreId(), $backup_archive_path, $pingback_url, $signature, $export_type);
    }

    public function deleteStore()
    {
        return (new StoreApi(
            $this->client(),
            $this->urlCreator()
        ))->delete($this->getStoreId());
    }

    /**
     * @param string $download_url
     * @param string $archive_size
     */
    public function onStoreExportPingback(IUser $recipient, array $export_memory, $download_url, $archive_size)
    {
        $recipients = [$recipient];

        if ($recipient->getEmail() != 'tech-support@activecollab.com') {
            $recipients[] = new AnonymousUser('Technical Support', 'tech-support@activecollab.com');
        }

        /** @var StoreExportNotification $notification */
        $notification = AngieApplication::notifications()->notifyAbout('system/store_export');
        $notification
            ->setDownloadUrl($download_url)
            ->setExportType($export_memory['export_type'])
            ->setUserName($recipient->getFirstName())
            ->setArchiveSize(format_file_size($archive_size))
            ->sendToUsers($recipients);
    }

    /**
     * Generate default pingback URL.
     *
     * @return string
     */
    public function getDefaultPingbackUrl()
    {
        return AngieApplication::getContainer()
            ->get(RouterInterface::class)
                ->assemble('warehouse_store_export_complete_pingback');
    }

    /**
     * @return FileApi
     */
    public function getFileApi()
    {
        return new FileApi($this->client(), $this->urlCreator());
    }

    private function addTokenPlaceholderToUrl(string $url, string $token_placeholder)
    {
        return strpos($url, '?') === false
            ? $url . '?' . $token_placeholder
            : $url . '&' . $token_placeholder;
    }
}
