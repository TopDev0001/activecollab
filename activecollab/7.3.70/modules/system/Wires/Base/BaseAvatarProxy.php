<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Wires\Base;

use ActiveCollab\Foundation\App\Proxy\ProxyRequestHandler;
use mysqli;

abstract class BaseAvatarProxy extends ProxyRequestHandler
{
    const SIZES = [
        20,
        40,
        80,
        256,
    ];

    const DEFAULT_SIZE = 40;
    const DEFAULT_TEXT = 'NA';

    protected int $size;

    public function __construct(array $params = null)
    {
        $this->size = !empty($params['size']) ? (int) $params['size'] : 0;

        if (!in_array($this->size, self::SIZES)) {
            $this->size = self::DEFAULT_SIZE;
        }
    }

    protected function getDatabaseConnection(): ?mysqli
    {
        $connection = mysqli_connect(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME
        );

        if (!empty($connection)) {
            $connection->set_charset('utf8mb4');

            return $connection;
        }

        return null;
    }

    protected function avatarNotChanged(string $etag): void
    {
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename=avatar.png');
        header('Cache-Control: public, max-age=315360000');
        header('Pragma: public');
        header('Etag: ' . $etag);

        $this->notModified();
    }

    protected function renderAvatarFromText(string $text, string $etag): void
    {
        require_once APPLICATION_PATH . '/vendor/autoload.php';

        if ($this->getCachedEtag() === $etag) {
            $this->avatarNotChanged($etag);

            return;
        }

        if (empty($text)) {
            $text = self::DEFAULT_TEXT;
        }

        $filename = sprintf(
            '%s/default_%s_avatar_%s_%d.png',
            WORK_PATH,
            $this->getAvatarContext(),
            md5($text),
            $this->size
        );

        if (!file_exists($filename)) {
            generate_avatar_with_initials($filename, $this->size, $text);
        }

        if (file_exists($filename)) {
            header('Content-Type: image/png');
            header('Content-Disposition: inline; filename=avatar.png');
            header('Cache-Control: public, max-age=315360000');
            header('Pragma: public');
            header('Etag: ' . $etag);
            print file_get_contents($filename);
            exit();
        }

        $this->notFound();
    }

    protected function renderAvatarFromSource(
        string $source_file,
        string $tag,
        bool $resize_image = true
    )
    {
        $thumb_file = sprintf(
            '%s/upload-%s-avatar-%s-%dx%d-crop',
            THUMBNAILS_PATH,
            $this->getAvatarContext(),
            $tag,
            $this->size,
            $this->size
        );

        if (!is_file($thumb_file)) {
            if ($resize_image) {
                scale_and_crop_image_alt(
                    $source_file,
                    $thumb_file,
                    $this->size * 2,
                    $this->size * 2,
                    null,
                    null,
                    IMAGETYPE_PNG
                );
            } else {
                copy($source_file, $thumb_file);
            }
        }

        if (is_file($thumb_file)) {
            header('Content-Type: image/png');
            header('Content-Disposition: inline; filename=avatar.png');
            header('Cache-Control: public, max-age=315360000');
            header('Pragma: public');
            header('Etag: ' . $tag);

            print file_get_contents($thumb_file);
            exit();
        }
    }

    protected function getTagFromSourceFile(string $source_file): ?string
    {
        $hash = md5_file($source_file);

        if (empty($hash)) {
            return null;
        }

        return $hash;
    }

    abstract protected function getAvatarContext(): string;
}
