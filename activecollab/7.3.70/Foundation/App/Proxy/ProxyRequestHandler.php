<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Proxy;

use const ASSETS_PATH;

abstract class ProxyRequestHandler
{
    abstract public function execute();

    public function success(): void
    {
        header('HTTP/1.1 200 OK');
        exit();
    }

    public function notModified(): void
    {
        header('HTTP/1.1 304 Not Modified');
        exit();
    }

    public function notFound(): void
    {
        header('HTTP/1.1 404 HTTP/1.1 404 Not Found');
        exit('<h1>HTTP/1.1 404 Not Found</h1>');
    }

    public function badRequest(): void
    {
        header('HTTP/1.1 400 HTTP/1.1 400 Bad Request');
        exit('<h1>HTTP/1.1 400 Bad Request</h1>');
    }

    public function operationFailed(): void
    {
        header('HTTP/1.1 500 HTTP/1.1 500 Internal Server Error');
        exit('<h1>HTTP/1.1 500 Internal Server Error</h1>');
    }

    public function unprocessableEntity(): void
    {
        header('HTTP/1.1 422 HTTP/1.1 422 Unprocessable Entity');
        exit('<h1>HTTP/1.1 422 Unprocessable Entity</h1>');
    }

    public function redirect(string $url, bool $moved_permanently = false): void
    {
        header('Location: ' . $url, true, $moved_permanently ? 301 : 302);
        exit();
    }

    protected function getCachedEtag(): ?string
    {
        return !empty($_SERVER['HTTP_IF_NONE_MATCH'])
            ? (string) $_SERVER['HTTP_IF_NONE_MATCH']
            : null;
    }

    public function fileLocked()
    {
        header('HTTP/1.1 400 HTTP/1.1 400 Bad Request');
        header('Content-type: image/png');
        header('Expires: Mon, 1 Jan 2099 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        $file = ASSETS_PATH . '/system/images/locked_thumbnail.png';

        $size = filesize($file);
        header("Content-Length: $size bytes");

        readfile($file);

        exit();
    }

    protected function downloadWarehouseFile(
        string $file_path,
        string $file_md5,
        string $access_token,
        string $download_to
    ): bool
    {
        $authorization = 'Authorization: Bearer ' . $access_token;

        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_URL,
            sprintf(
                '%s/api/v1/files/%s/%s/internal/download',
                WAREHOUSE_URL,
                $file_path,
                $file_md5
            )
        );
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', $authorization]);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return false;
        }

        $fp = fopen($download_to, 'w');
        fwrite($fp, $result);
        fclose($fp);

        return true;
    }
}
