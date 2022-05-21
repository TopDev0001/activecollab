<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Wires\ForwardThumbnailProxy;

abstract class FwThumbnails
{
    const SCALE = 'scale'; // Proportionally scale down to the given dimensions
    const CROP = 'crop'; // Crop from the middle of the image while forcing the full dimensions

    /**
     * Return thumbnail URL.
     *
     * @param  string $source
     * @param  string $location
     * @param  string $original_file_name
     * @param  int    $width
     * @param  int    $height
     * @param  string $scale
     * @return string
     */
    public static function getUrl($source, $location, $original_file_name, $width, $height, $scale = self::SCALE)
    {
        if (str_starts_with($source, UPLOAD_PATH)) {
            $context = 'upload';
        } elseif (str_starts_with($source, WORK_PATH)) {
            $context = 'work';
        } else {
            throw new InvalidParamError('source', $source, 'Thumbnails can be created only from uploaded and work files');
        }

        if ($width != '--WIDTH--') {
            $width = (int) $width;

            if ($width < 1) {
                $width = 80;
            }
        }

        if ($height != '--HEIGHT--') {
            $height = (int) $height;

            if ($height < 1) {
                $height = 80;
            }
        }

        return AngieApplication::getProxyUrl(
            ForwardThumbnailProxy::class,
            EnvironmentFramework::INJECT_INTO,
            [
                'context' => $context,
                'name' => $location,
                'original_file_name' => $original_file_name,
                'width' => $width,
                'height' => $height,
                'ver' => file_exists($source) ? filesize($source) : 0,
                'scale' => $scale,
            ]
        );
    }

    /**
     * calculate cached previews size.
     */
    public static function cacheSize()
    {
        return dir_size(THUMBNAILS_PATH, true);
    }

    /**
     * Remove all cached previews.
     *
     * @return bool
     */
    public static function cacheClear()
    {
        return empty_dir(THUMBNAILS_PATH, true);
    }
}
