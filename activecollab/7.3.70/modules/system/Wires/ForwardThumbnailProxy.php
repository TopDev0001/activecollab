<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Wires;

use ActiveCollab\Module\System\Wires\Base\FileProxy;

class ForwardThumbnailProxy extends FileProxy
{
    /**
     * Context where to look for source file.
     *
     * @var string
     */
    protected $context;

    /**
     * Source file with full path based on context.
     *
     * @var string
     */
    protected $source;

    /**
     * Name of the source file in /upload folder.
     *
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $extension;

    /**
     * Name of the original file.
     *
     * @var string
     */
    protected $original_file_name;

    /**
     * Image width (in px).
     *
     * @var int
     */
    protected $width;

    /**
     * Image height (in px).
     *
     * @var int
     */
    protected $height;

    /**
     * Scaling method.
     *
     * @var int
     */
    protected $scale;

    /**
     * Image size (in bytes).
     *
     * @var int
     */
    protected $size;

    protected array $recognized_file_formats = [
        'aftereffects' => ['aep'],
        'ai' => ['ai'],
        'code' => ['htm', 'html', 'css', 'php', 'rb', 'py', 'js'],
        'executable' => ['app', 'bat', 'exe', 'jar', 'phar', 'wsf', 'pkg', 'msi'],
        'font' => ['fnt', 'fon', 'otf', 'ttf'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
        'indesign' => ['indd'],
        'keynote' => ['ppt', 'pptx', 'key'],
        'lightroom' => ['lrcat', 'lrdata'],
        'pdf' => ['pdf'],
        'premiere' => ['ppj'],
        'psd' => ['psd'],
        'sheet' => ['csv', 'xls', 'xlsx', 'numbers'],
        'sound' => ['m4a', 'mpa', 'mp3', 'mid', 'wav', 'wma'],
        'video' => ['avi', 'mkv', 'flv', 'mov', 'mp4', 'mpg', 'swf', 'wmv'],
        'wordrtf' => ['doc', 'docx', 'pages', 'rtf', 'txt', 'wpd'],
        'zip' => ['zip', 'zipx', 'rar', '7z', 'gz', 'bz'],
    ];

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
        $this->name = isset($params['name']) && $params['name'] ? $params['name'] : null;
        $this->original_file_name = isset($params['original_file_name']) && $params['original_file_name'] ? trim($params['original_file_name']) : null;
        $this->extension = strtolower(get_file_extension($this->original_file_name));
        $this->size = isset($params['ver']) && $params['ver'] ? (int) $params['ver'] : 0;
        $this->width = isset($params['width']) && $params['width'] ? (int) $params['width'] : 0;
        $this->height = isset($params['height']) && $params['height'] ? (int) $params['height'] : 0;
        $this->scale = isset($params['scale']) && $params['scale'] ? trim($params['scale']) : null;
        $this->authorized_at = isset($params['authorized_at']) && $params['authorized_at'] ? (int) $params['authorized_at'] : null;
    }

    /**
     * Forward thumbnail.
     */
    public function execute()
    {
        if (empty($this->name) || empty($this->width) || empty($this->height)) {
            $this->notFound();
        }

        if ($this->context == 'upload') {
            $this->source = UPLOAD_PATH . '/' . $this->name;
        } elseif ($this->context == 'work') {
            $this->source = WORK_PATH . '/' . $this->name;
        } else {
            $this->source = null;
        }

        $image_size = "{$this->width}_{$this->height}";

        $this->verifyAccessPermission(
            'forward_thumbnail',
            $this->name,
            null,
            $this->authorized_at,
            null,
            $image_size
        );

        // full path to the location of the thumbnail
        $thumb_file = THUMBNAILS_PATH . "/{$this->context}-" . str_replace('/', '-', $this->name) . "-{$this->width}x{$this->height}-$this->scale";

        // if there is no source file generate empty image and serve it
        if (!is_file($this->source)) {
            $this->imageNotFoundThumbnail($thumb_file);
        }

        // size on file system and provided size must match
        if (filesize($this->source) != $this->size) {
            $this->notFound();
        }

        // send headers
        header('Cache-Control: public, max-age=315360000');
        header('Pragma: public');

        // thumbnail already exists, download it
        if (is_file($thumb_file)) {
            $current_md5 = $this->generateThumbnailHash($thumb_file);
            header('Etag: ' . $current_md5);

            $cached_hash = $this->getCachedEtag();
            if ($cached_hash && $cached_hash == $current_md5) {
                $this->notModified();
            }

            $this->downloadThumbnail($thumb_file);
        }

        // file_extension
        $full_preview_result = false;

        // proceed and generate rich thumbnail
        switch ($this->getSourceType($this->source, $this->original_file_name)) {
            case self::SOURCE_IMAGE:
                $full_preview_result = $this->generateFromImage($this->source, $thumb_file, $this->width, $this->height, $this->scale);
                break;
            case self::SOURCE_PDF:
                $full_preview_result = $this->generateFromPdf($this->source, $thumb_file, $this->width, $this->height, $this->scale);
                break;
            case self::SOURCE_PSD:
                $full_preview_result = $this->generateFromPsd($this->source, $thumb_file, $this->width, $this->height, $this->scale);
                break;
        }

        // rich thumbnail does not exist, generate thumbnail based on file icon
        if (!$full_preview_result) {
            $this->generateBasedOnFileExtension($this->extension, $thumb_file, $this->width, $this->height, $this->scale);
        }

        if (is_file($thumb_file)) {
            header('Etag: ' . $this->generateThumbnailHash($thumb_file));
            $this->downloadThumbnail($thumb_file);
        } else {
            $this->notFound();
        }
    }

    private function downloadThumbnail($path)
    {
        $thumb_mime_type = get_mime_type($path);
        if ($thumb_mime_type === 'image/png') {
            $thumb_file_name = 'thumbnail.png';
        } else {
            $thumb_file_name = 'thumbnail.jpg';
            $thumb_mime_type = 'image/jpeg';
        }

        download_file($path, $thumb_mime_type, $thumb_file_name, false, true);
    }

    /**
     * @param  string $file
     * @return string
     */
    protected function generateThumbnailHash($file)
    {
        return md5($file);
    }

    /**
     * Generate thumbnail based on file extension.
     *
     * @param  string $extension
     * @param  string $thumb_file
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return bool
     */
    protected function generateBasedOnFileExtension($extension, $thumb_file, $width, $height, $scale)
    {
        $format = 'blank';

        foreach ($this->recognized_file_formats as $recognized_format => $extensions) {
            if (in_array($extension, $extensions)) {
                $format = $recognized_format;
                break;
            }
        }

        $source = ANGIE_PATH . "/frameworks/environment/assets/file-types/{$format}" . ($width >= 48 && $height >= 48 ? '@2x' : '') . '.png';

        scale_image_and_force_size($source, $thumb_file, $width, $height, IMAGETYPE_PNG, 100);

        return true;
    }

    /**
     * Create an empty image for situation when source is not found.
     *
     * @param string $thumb_file
     */
    protected function imageNotFoundThumbnail($thumb_file)
    {
        if (extension_loaded('gd')) {
            $image = imagecreatetruecolor($this->width, $this->height);
            $text_color = imagecolorallocate($image, 255, 255, 255);
            imagestring($image, 2, 5, 5, 'Not Found', $text_color);
            imagejpeg($image, $thumb_file, 80);
            imagedestroy($image);

            $this->downloadThumbnail($thumb_file);
        } else {
            $this->notFound();
        }
    }
}
