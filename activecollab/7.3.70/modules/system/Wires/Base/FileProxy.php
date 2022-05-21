<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Wires\Base;

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use ActiveCollab\ActiveCollabJwt\Verifier\LcobucciJwtVerifier;
use ActiveCollab\Foundation\App\Proxy\ProxyRequestHandler;
use Angie\Utils\ProxyActionResolver;
use Exception;
use imagick;
use ImagickPixel;
use Lcobucci\JWT\Validation\ConstraintViolation;
use LogicException;

abstract class FileProxy extends ProxyRequestHandler
{
    public const LOG_DEBUG = 'DEBUG';
    public const LOG_ERROR = 'ERROR';
    public const LOG_INFO = 'INFO';
    public const LOG_WARNING = 'WARNING';
    public const LOG_LEVELS = [
        self::LOG_DEBUG,
        self::LOG_ERROR,
        self::LOG_INFO,
        self::LOG_WARNING,
    ];

    // Scaling method
    public const SCALE = 'scale'; // Proportionally scale down to the given dimensions
    public const CROP = 'crop'; // Crop from the middle of the image while forcing the full dimensions

    // Source types
    public const SOURCE_IMAGE = 'image';
    public const SOURCE_PDF = 'pdf';
    public const SOURCE_PSD = 'psd';
    public const SOURCE_OTHER = 'other';
    public const SECONDS_IN_24H = 86400;
    private ?string $intent;
    private int $current_timestamp;

    public function __construct(
        int $current_timestamp,
        ?string $intent
    )
    {
        $this->intent = $intent;

        require_once ANGIE_PATH . '/functions/general.php';
        require_once ANGIE_PATH . '/functions/web.php';
        require_once ANGIE_PATH . '/functions/files.php';
        $this->current_timestamp = $current_timestamp;
    }

    protected function verifyAccessPermission(
        string $proxy,
        string $location,
        ?string $hash,
        ?int $authorized_at,
        ?bool $force = false,
        ?string $image_size = null,
        ?string $scale = null
    )
    {
        if (!$this->isIntentCheckActive()) {
            return;
        }

        if (!$this->intent) {
            $this->notFound();
        }

        require_once APPLICATION_PATH . '/vendor/autoload.php';

        try {
            $jwt_verifier = new LcobucciJwtVerifier(
                ROOT_URL
            );

            $claims = $jwt_verifier
                ->verify(
                    JwtFactoryInterface::SIGNER_HMAC_SHA256,
                    defined('FILE_ACCESS_TOKEN_KEY') ? (string) FILE_ACCESS_TOKEN_KEY : (string) LICENSE_KEY,
                    $this->intent,
                    ROOT_URL
                );

            if ((new ProxyActionResolver())->resolveActionIntent($proxy) !== $claims['intent']) {
                throw new LogicException("File intent doesn't match!");
            }
        } catch (ConstraintViolation $exception) {
            if ($exception->getMessage() === 'The token is expired') {
                if ($proxy === 'download_attachments_archive') {
                    $this->badRequest();
                }

                if (empty($authorized_at) || !$this->isAuthorizedInLast24h($authorized_at)) {
                    $action = (new ProxyActionResolver())->resolveActionIntent($proxy);
                    $this->redirectToAuthorize($location, $hash, $force, $action, $image_size, $scale);
                } else {
                    $this->fileLocked();
                }
            } else {
                $this->log($exception->getMessage(), self::LOG_ERROR);
                $this->notFound();
            }
        } catch (Exception $exception) {
            $this->log($exception->getMessage(), self::LOG_ERROR);
            $this->notFound();
        }
    }

    /**
     * Get file based on pieces of data.
     *
     * @param  string $context
     * @param  int    $id
     * @param  int    $size
     * @param  string $md5
     * @param  string $timestamp
     * @return array
     */
    protected function getFile($context, $id, $size, $md5, $timestamp)
    {
        if ($context === null || $id === null || $size === null || $md5 === null || $timestamp === null) {
            $this->badRequest();
        }

        $table_name = $this->contextToTableName($context);
        if (empty($table_name)) {
            $this->badRequest();
        }

        $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME); // connect to database

        if (empty($connection)) {
            $this->operationFailed();
        }

        $connection->set_charset('utf8');

        if ($table_name === 'project_template_elements') {
            $query = sprintf('SELECT name, raw_additional_properties FROM ' . $table_name . " WHERE id='%s' AND created_on='%s'",
                $connection->real_escape_string($id),
                $connection->real_escape_string($timestamp)
            );
        } else {
            // create query
            $query = sprintf('SELECT `type`, location, name, mime_type FROM ' . $table_name . " WHERE id='%s' AND size='%s' AND md5='%s' AND created_on='%s'",
                $connection->real_escape_string($id),
                $connection->real_escape_string($size),
                $connection->real_escape_string($md5),
                $connection->real_escape_string($timestamp)
            );
        }

        // extract file details
        $result = $connection->query($query);
        if ($result == false) {
            $this->notFound();
        }

        $file = $result->fetch_assoc();

        if (isset($file['raw_additional_properties'])) {
            $file = array_merge($file, unserialize($file['raw_additional_properties']));
            unset($file['raw_additional_properties']);

            if (!(isset($file['size']) && $file['size'] == $size)) {
                $this->notFound();
            }

            if (!(isset($file['md5']) && $file['md5'] == $md5)) {
                $this->notFound();
            }
        }

        if (!(isset($file['location']) && $file['location'])) {
            $this->notFound();
        }

        return $file;
    }

    /**
     * Generate thumbnail from image source.
     *
     * @param  string $source
     * @param  string $thumb_file
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return bool
     */
    protected function generateFromImage($source, $thumb_file, $width, $height, $scale)
    {
        try {
            if ($scale == self::SCALE) {
                scale_and_fit_image($source, $thumb_file, $width, $height, IMAGETYPE_JPEG, 100);
            } else {
                scale_and_crop_image_alt($source, $thumb_file, $width, $height, null, null, IMAGETYPE_JPEG, 100);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Generate a thumbnail from a PDF.
     *
     * @param  string $source
     * @param  string $thumb_file
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return bool
     */
    protected function generateFromPdf($source, $thumb_file, $width, $height, $scale)
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            $magic = new imagick(); // create imagick instance
            $magic->setResolution(200, 200); // set resolution before loading image
            $magic->readImage($source . '[0]'); // [0] means first page
            $magic->setimageformat('jpeg');
            $magic->setImageCompressionQuality(80);

            // Flatten image before resizing and if pdf has transparent background
            if (method_exists($magic, 'flattenImages')) {
                $magic = $magic->flattenImages();
            } else {
                $alphachannel_remove = defined('imagick::ALPHACHANNEL_REMOVE')
                    ? imagick::ALPHACHANNEL_REMOVE
                    : 11;

                $magic->setImageBackgroundColor('white');
                $magic->setImageAlphaChannel($alphachannel_remove);
                $magic->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            }

            // resize/crop image
            if ($scale == self::SCALE) {
                $magic->thumbnailimage($width, $height, true);
            } else {
                $magic->cropthumbnailimage($width, $height);
            }

            $magic->writeimage($thumb_file); // save image
            $magic->clear();
            $magic->destroy();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Generate a thumbnail from a PSD.
     *
     * @param  string $source
     * @param  string $thumb_file
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return bool
     */
    protected function generateFromPsd($source, $thumb_file, $width, $height, $scale)
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            $magic = new Imagick();
            $magic->setResolution(200, 200); // set resolution before loading image
            $magic->setBackgroundColor(new ImagickPixel('transparent'));
            $magic->readImage($source . '[0]');
            $magic->setimageformat('jpeg');
            $magic->setImageCompressionQuality(80);

            if ($scale == self::SCALE) {
                $magic->thumbnailimage($width, $height, true);
            } else {
                $magic->cropthumbnailimage($width, $height);
            }

            $magic->writeimage($thumb_file);
            $magic->clear();
            $magic->destroy();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Return source type based on source file and original name.
     *
     * @param  string $source_path
     * @param  string $original_name
     * @return string
     */
    protected function getSourceType($source_path, $original_name)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime_type = finfo_file($finfo, $source_path);

        if (in_array($mime_type, ['image/jpg', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/png'])) {
            return self::SOURCE_IMAGE;
        } elseif (in_array($mime_type, ['image/photoshop', 'image/x-photoshop', 'image/vnd.adobe.photoshop', 'image/psd', 'application/photoshop', 'application/psd'])) {
            return self::SOURCE_PSD;
        } elseif ($mime_type == 'application/pdf') {
            return self::SOURCE_PDF;
        } else {
            return self::SOURCE_OTHER;
        }
    }

    /**
     * @param  string      $context
     * @return string|null
     */
    protected function contextToTableName($context)
    {
        return $context === 'attachments' ? 'attachments' : null;
    }

    protected function getAvailableFileName(
        string $dir_path,
        string $prefix = null,
        string $extension = null,
        bool $random_string = true
    ): string
    {
        if ($prefix) {
            $prefix = $this->getAccountId() . "-{$prefix}-";
        } else {
            $prefix = $this->getAccountId() . '-';
        }

        if ($extension) {
            $extension = ".$extension";
        }

        if ($random_string) {
            do {
                $filename = $dir_path . '/' . $prefix . make_string(10) . $extension;
            } while (is_file($filename));
        } else {
            $filename = trim($dir_path . '/' . $prefix, '-') . $extension;
        }

        return $filename;
    }

    private function getAccountId(): int
    {
        if (defined('ON_DEMAND_INSTANCE_ID')) {
            return (int) ON_DEMAND_INSTANCE_ID;
        } else {
            return (int) explode('/', LICENSE_KEY)[1];
        }
    }

    protected function log($message, $level = self::LOG_INFO)
    {
        $level = in_array($level, self::LOG_LEVELS) ? $level : self::LOG_DEBUG;

        $date_time = new \ActiveCollab\DateValue\DateTimeValue();
        $message = '[' . $date_time->format('Y-m-d H:m:i') . '] ' . $level . ': ' . $message . "\n";
        error_log($message, 3, ENVIRONMENT_PATH . '/logs/proxy-log.txt');
    }

    private function isIntentCheckActive(): bool
    {
        $flags = defined('ACTIVECOLLAB_FEATURE_FLAGS') ? ACTIVECOLLAB_FEATURE_FLAGS : [];

        if (is_string($flags)) {
            $flags = explode(',', $flags);
        }

        return in_array('files_jwt_protected', $flags);
    }

    private function redirectToAuthorize(
        string $location,
        ?string $hash,
        ?bool $force,
        string $action,
        ?string $image_size,
        ?string $scale
    ): void
    {
        $hash = $hash ? "&hash={$hash}" : '';
        $force = $force ? '&force=1' : '';
        $image_size = $image_size ? "&size={$image_size}" : '';
        $scale = $scale ? "&scale={$scale}" : '&scale=' . self::CROP;
        $url = ROOT_URL . "/authorize-file-access?location={$location}{$hash}&intent={$action}{$image_size}{$force}{$scale}";

        $this->redirect($url);
    }

    protected function isAuthorizedInLast24h(int $authorized_at): bool
    {
        return $authorized_at > $this->current_timestamp - self::SECONDS_IN_24H;
    }
}
