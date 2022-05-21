<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Wires\DownloadFileProxy;
use ActiveCollab\Module\System\Wires\ForwardPreviewProxy;
use Angie\Http\Response\FileDownload\FileDownload;

trait IFileImplementation
{
    public function registerIFileImplementation()
    {
        $this->registerEventHandler('on_json_serialize', function (array & $result) {
            $result['mime_type'] = $this->getMimeType();
            $result['size'] = $this->getSize();
            $result['md5'] = $this->getMd5();
            $result['thumbnail_url'] = $this->getThumbnailUrl('--WIDTH--', '--HEIGHT--', '--SCALE--');
            $result['preview_url'] = $this->getPreviewUrl();
            $result['download_url'] = $this->getDownloadUrl(true);
            $result['file_meta'] = $this->getFileMeta();
        });

        // ---------------------------------------------------
        //  Handle file deletion
        // ---------------------------------------------------

        $file_location_before_file_is_deleted = null;
        $file_type_before_file_is_deleted = null;

        $this->registerEventHandler(
            'on_before_delete',
            function () use (&$file_location_before_file_is_deleted, &$file_type_before_file_is_deleted) {
                $file_location_before_file_is_deleted = $this->getLocation();
                $file_type_before_file_is_deleted = $this->getFileType();
            }
        );

        $this->registerEventHandler(
            'on_after_delete',
            function () use (&$file_location_before_file_is_deleted, &$file_type_before_file_is_deleted) {
                if (empty($this->keep_file_on_delete)) {
                    if ($file_location_before_file_is_deleted && $file_type_before_file_is_deleted) {
                        AngieApplication::storage()->deleteFile(
                            $file_type_before_file_is_deleted,
                            $file_location_before_file_is_deleted
                        );
                    }
                }
            }
        );
    }

    /**
     * Return file type, so we know what we are dealing with here.
     *
     * This methood has been extracted so it can be overriden. Some objects act like files (project template files for
     * example), and they need to communicate which exact file type are wrapping around.
     */
    protected function getFileType(): string
    {
        return get_class($this);
    }

    /**
     * Return MD5 hash of this file version.
     *
     * @return string
     */
    public function getMd5()
    {
        $md5 = parent::getMd5();

        if (empty($md5) && $this->getId()) {
            $path = $this->getPath();

            if (is_file($path)) {
                $md5 = md5_file($path);

                if ($md5) {
                    $this->setMd5($md5);
                    $this->save();
                }
            }
        }

        return $md5;
    }

    /**
     * Return file path.
     *
     * @return string
     */
    public function getPath()
    {
        return AngieApplication::fileLocationToPath($this->getLocation());
    }

    /**
     * Return file meta.
     *
     * @return array
     */
    public function getFileMeta()
    {
        return AngieApplication::cache()->getByObject($this, 'file_meta', function () {
            $result = [];

            try {
                $type = $this->getFilePreviewType();

                $result = ['kind' => $type];

                if (!$this->isRemote()) {
                    $path = $this->getPath();

                    if (is_file($path)) {
                        // Dimensions for images and PSD-s
                        if ($type == IFile::IMAGE || $type == IFile::PSD) {
                            if (extension_loaded('gd')) {
                                $size = getimagesize($path);

                                if ($size && is_array($size)) {
                                    $width = $size[0];
                                    $height = $size[1];
                                } else {
                                    AngieApplication::log()->warning('Could not gather image info - file may be corrupted', [
                                        'file_path' => $path,
                                        'file_id' => $this->getId(),
                                        'file_type' => $type,
                                    ]);
                                }
                            } elseif (extension_loaded('imagick')) {
                                $image = new Imagick();
                                $image->pingImage($this->getPath());

                                $width = $image->getImageWidth();
                                $height = $image->getImageHeight();
                            }

                            if (isset($width) && isset($height)) {
                                $result['dimensions'] = ['width' => $width, 'height' => $height];
                            }

                        // Number of pages for PDF documents
                        } elseif ($type == IFile::PDF && extension_loaded('imagick')) {
                            $result['pages'] = 1;
                        }
                    } else {
                        AngieApplication::log()->warning('Could not gather file meta data - file does not exist on the path', [
                            'file_path' => $path,
                            'file_id' => $this->getId(),
                            'file_type' => $type,
                        ]);
                    }
                }
            } catch (Exception $e) {
                AngieApplication::log()->warning('Could not gather file meta data: {reason}', [
                    'file_id' => $this->getId(),
                    'reason' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }

            return $result;
        });
    }

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
    public function setContentFromFile($path, $save = true)
    {
        if (is_file($path) && is_readable($path)) {
            $destination_file = AngieApplication::getAvailableUploadsFileName();

            if (copy($path, $destination_file)) {
                $this->setName(basename($path));
                $this->setSize(filesize($path));
                $this->setLocation(basename($destination_file));
                $this->setMd5(md5_file($path));
                $this->setMimeType(get_mime_type($path, $this->getName()));

                if ($save) {
                    $this->save();
                }

                return $destination_file;
            } else {
                throw new FileCopyError($path, $destination_file);
            }
        } else {
            throw new FileDnxError($path);
        }
    }

    /**
     * Set file properties from existing file on disk.
     *
     * If $save is set to true, save() method of parent object will be called.
     * This function returns path of destination file on success
     *
     * @param  array             $file
     * @param  bool              $save
     * @return string
     * @throws FileCopyError
     * @throws FileDnxError
     * @throws InvalidParamError
     * @throws UploadError
     */
    public function setContentFromUploadedFile($file, $save = true)
    {
        if (is_array($file)) {
            if (isset($file['error']) && $file['error'] > 0) {
                throw new UploadError($file['error']);
            }

            $destination_file = AngieApplication::getAvailableUploadsFileName();
            if (move_uploaded_file($file['tmp_name'], $destination_file)) {
                $file_name = array_var($file, 'name');

                $this->setName($file_name);
                $this->setSize((int) array_var($file, 'size'));
                $this->setMimeType(array_var($file, 'type'));
                $this->setLocation(basename($destination_file));
                $this->setMd5(md5_file($destination_file));

                if ($save) {
                    $this->save();
                }

                return $destination_file;
            } else {
                throw new FileCopyError($file['tmp_name'], $destination_file);
            }
        } else {
            throw new InvalidParamError('file', $file, '$file is not a valid uploaded file instance');
        }
    }

    // ---------------------------------------------------
    //  Preview
    // ---------------------------------------------------

    /**
     * Return preview type.
     *
     * @return string
     */
    protected function getFilePreviewType()
    {
        if (in_array($this->getMimeType(), ['image/jpg', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/png'])) {
            return IFile::IMAGE;
        }

        $file_extension = strtolower(get_file_extension($this->getName()));

        // determine preview type by extension
        switch ($file_extension) {
            case 'flv':
            case 'mp4':
            case 'm4v':
            case 'f4v':
            case 'mov':
            case 'webm':
                return IFile::VIDEO;
            case 'mp3':
            case 'aac':
            case 'm4a':
            case 'f4a':
            case 'ogg':
            case 'oga':
                return IFile::AUDIO;
            case 'swf':
                return IFile::FLASH;
            case 'pdf':
                return IFile::PDF;
            case 'psd':
                return IFile::PSD;
        }

        return IFile::OTHER;
    }

    /**
     * Return file thumbnail URL.
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return string
     */
    public function getThumbnailUrl($width = 80, $height = 80, $scale = Thumbnails::SCALE)
    {
        return Thumbnails::getUrl($this->getPath(), $this->getLocation(), $this->getName(), $width, $height, $scale) . '&crop=1';
    }

    /**
     * Return download URL.
     *
     * @param  bool   $force
     * @return string
     */
    public function getDownloadUrl($force = false)
    {
        return AngieApplication::getProxyUrl(
            DownloadFileProxy::class,
            AttachmentsFramework::INJECT_INTO,
            [
                'context' => $this->getTableName(),
                'id' => $this->getId(),
                'size' => $this->getSize(),
                'md5' => $this->getMd5(),
                'timestamp' => $this->getCreatedOn()->toMySQL(),
                'force' => $force,
            ]
        );
    }

    /**
     * Get preview url.
     *
     * @return string
     */
    public function getPreviewUrl()
    {
        return AngieApplication::getProxyUrl(
            ForwardPreviewProxy::class,
            AttachmentsFramework::INJECT_INTO,
            [
              'context' => $this->getTableName(),
              'id' => $this->getId(),
              'size' => $this->getSize(),
              'md5' => $this->getMd5(),
              'timestamp' => $this->getCreatedOn()->toMySQL(),
          ]
        );
    }

    /**
     * Return public download URL.
     *
     * @param  bool   $force
     * @return string
     */
    public function getPublicDownloadUrl($force = false)
    {
        return $this->getDownloadUrl($force);
    }

    /**
     * Keep the binary file when we delete this object.
     *
     * @var bool
     */
    private $keep_file_on_delete = false;

    /**
     * Keep file on delete.
     *
     * @param bool $yes_or_no
     */
    public function keepFileOnDelete($yes_or_no)
    {
        $this->keep_file_on_delete = $yes_or_no;
    }

    /**
     * Prepare FileDownload instance.
     *
     * @return FileDownload
     */
    public function prepareForDownload()
    {
        return new FileDownload($this->getPath(), $this->getMimeType(), $this->getName());
    }

    public function isRemote()
    {
        return $this instanceof IRemoteFile;
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return object ID.
     *
     * @return int
     */
    abstract public function getId();

    /**
     * Return file name.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Set file name.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setName($value);

    /**
     * Return file size, in bytes.
     *
     * @return int
     */
    abstract public function getSize();

    /**
     * Set file size, in bytes.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setSize($value);

    /**
     * Return file location in UPLOAD_PATH folder.
     *
     * @return string
     */
    abstract public function getLocation();

    /**
     * Set file location.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setLocation($value);

    /**
     * Return file MIME type.
     *
     * @return string
     */
    abstract public function getMimeType();

    /**
     * Set file MIME type.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setMimeType($value);

    /**
     * Set MD5 hash value.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setMd5($value);

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return DateTimeValue
     */
    abstract public function getCreatedOn();

    /**
     * Return value of table name.
     *
     * @return string
     */
    abstract public function getTableName();

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
