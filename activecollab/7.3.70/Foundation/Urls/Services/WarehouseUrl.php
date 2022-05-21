<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Services;

use ActiveCollab\Foundation\Urls\ExternalUrl;
use AngieApplication;

class WarehouseUrl extends ExternalUrl implements WarehouseUrlInterface
{
    public function isFile(): bool
    {
        if ($this->file_data === null) {
            $this->extractFileData();
        }

        return !empty($this->file_data);
    }

    public function getFileLocation(): ?string
    {
        return $this->getFileDetail('location');
    }

    public function getFileMd5Hash(): ?string
    {
        return $this->getFileDetail('md5_hash');
    }

    public function getFileAction(): ?string
    {
        return $this->getFileDetail('action');
    }

    private function getFileDetail(string $detail): ?string
    {
        if ($this->file_data === null) {
            $this->extractFileData();
        }

        return !empty($this->file_data[$detail]) ? $this->file_data[$detail] : null;
    }

    private ?array $file_data = null;

    private function extractFileData(): void
    {
        $this->file_data = [];

        $url_path = trim($this->getParsedUrl()['path'] ?? '', '/');

        if (empty($url_path) || strpos($url_path, '/') === false) {
            return;
        }

        $path_bits = explode('/', $url_path);

        if (!$this->isValidPath($path_bits)) {
            return;
        }

        if ($this->isLocationInSinglePathElement($path_bits)) {
            $this->file_data = [
                'location' => $path_bits[3],
                'md5_hash' => $path_bits[4],
                'action' => $path_bits[5],
            ];
        } else {
            $this->file_data = [
                'location' => sprintf('%s/%s', $path_bits[3], $path_bits[4]),
                'md5_hash' => $path_bits[5],
                'action' => $path_bits[6],
            ];
        }
    }

    private function isValidPath(array $path_bits): bool
    {
        if (count($path_bits) < 6) {
            return false;
        }

        return $path_bits[0] === 'api'
            && $path_bits[1] === 'v1'
            && $path_bits[2] === 'files'
            && $this->isValidFileAction($path_bits);
    }

    private function isValidFileAction(array $path_bits): bool
    {
        if ($this->isLocationInSinglePathElement($path_bits)) {
            return in_array($path_bits[5], self::FILE_ACTIONS);
        }

        if (!empty($path_bits[6])) {
            return in_array($path_bits[6], self::FILE_ACTIONS);
        }

        AngieApplication::log()->error(
            'Failed to locate action in Warehouse URL "{url}" (at key 6).',
            [
                'url' => implode('/', $path_bits),
                'path_bits' => $path_bits,
            ]
        );

        return false;
    }

    private function isLocationInSinglePathElement(array $path_bits): bool
    {
        return strpos($path_bits[3], '%2') !== false;
    }
}
