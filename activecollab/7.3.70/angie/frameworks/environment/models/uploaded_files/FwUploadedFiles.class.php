<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class FwUploadedFiles extends BaseUploadedFiles
{
    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): UploadedFile
    {
        if (array_key_exists('ip_address', $attributes)) {
            unset($attributes['ip_address']);
        }

        return parent::update($instance, $attributes, $save);
    }

    /**
     * Add from file.
     *
     * @param  string                  $path
     * @param  string                  $filename
     * @param  string                  $mime_type
     * @param  bool                    $check_for_available_space
     * @return UploadedFile|DataObject
     */
    public static function addFile(
        $path,
        $filename = null,
        $mime_type = 'application/octet-stream',
        $check_for_available_space = true
    )
    {
        if (is_file($path) && is_readable($path)) {
            $filename = empty($filename) ? basename($path) : $filename;
            [$target_path, $location] = AngieApplication::storeFile($path);

            return UploadedFiles::create(
                [
                    'name' => $filename,
                    'mime_type' => $mime_type,
                    'size' => filesize($path),
                    'location' => $location,
                    'md5' => md5_file($target_path),
                ]
            );
        } else {
            throw new FileDnxError($path);
        }
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): UploadedFile
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = LocalUploadedFile::class;
        }
        $attributes['ip_address'] = AngieApplication::getVisitorIp();

        return parent::create($attributes, $save, $announce);
    }

    /**
     * Add uploaded file.
     *
     * Note: $check_uploaded_file can be set to FALSE if we need to test how this function behaves without having to
     * upload any files
     *
     * @param  array             $file
     * @param  bool              $check_uploaded_file
     * @return DataObject
     * @throws InvalidParamError
     * @throws UploadError
     */
    public static function addUploadedFile($file, $check_uploaded_file = true)
    {
        if (is_array($file)) {
            if (isset($file['error']) && $file['error'] > 0) {
                throw new UploadError($file['error']);
            }

            if ($check_uploaded_file && !is_uploaded_file($file['tmp_name'])) {
                throw new InvalidParamError('file', $file, '$file is not uploaded file');
            }

            [$target_path, $location] = AngieApplication::storeFile($file['tmp_name'], $check_uploaded_file);

            return UploadedFiles::create(['name' => $file['name'], 'mime_type' => $file['type'], 'size' => $file['size'], 'location' => $location, 'md5' => md5_file($target_path)]);
        }

        throw new InvalidParamError('file', $file, '$file is not a valid uploaded file instance');
    }

    /**
     * Return available file code.
     *
     * @return string
     */
    public static function getAvailableCode()
    {
        do {
            $code = make_string(40);
        } while (DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM uploaded_files WHERE code = ?', $code));

        return $code;
    }

    /**
     * Return uploaded files by the array of file codes.
     *
     * @param  string                  $code
     * @return UploadedFile|DataObject
     */
    public static function findByCode($code)
    {
        return UploadedFiles::findOne(
            [
                'conditions' => [
                    'code = ?',
                    $code,
                ],
            ]
        );
    }

    /**
     * Return uploaded files by the array of file codes.
     *
     * @return UploadedFile[]|DataObject[]
     */
    public static function findByCodes(array $codes)
    {
        return UploadedFiles::find(
            [
                'conditions' => [
                    'code IN (?)',
                    $codes,
                ],
                'order' => 'created_on, id',
            ]
        );
    }

    public static function cleanUp(): void
    {
        $files = UploadedFiles::find(
            [
                'conditions' => [
                        'created_on < ?',
                        DateTimeValue::makeFromString('-7 days'),
                    ],
                'order' => 'created_on DESC',
            ]
        );

        if ($files) {
            /** @var UploadedFile[] $files */
            foreach ($files as $file) {
                $file->delete();
            }
        }
    }
}
