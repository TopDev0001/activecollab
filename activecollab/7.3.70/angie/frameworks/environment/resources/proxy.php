<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\App\Proxy\ProxyRequestHandler;
use ActiveCollab\Module\System\Wires\DownloadAttachmentsArchiveProxy;
use ActiveCollab\Module\System\Wires\DownloadFileProxy;
use ActiveCollab\Module\System\Wires\ForwardPreviewProxy;
use ActiveCollab\Module\System\Wires\ForwardThumbnailProxy;
use Angie\Inflector;
use SebastianBergmann\CodeCoverage\CodeCoverage;

require_once ROOT . '/' . APPLICATION_VERSION . '/vendor/autoload.php';

if (isset($this) && $this instanceof CodeCoverage) {
    return;
}

// Make sure that request is routed through /instance/proxy.php
if (!(defined('PROXY_HANDLER_REQUEST') && PROXY_HANDLER_REQUEST)) {
    header('HTTP/1.0 404 Not Found');
}

$proxy_name = null;
if (isset($_GET['proxy'])) {
    $proxy_name = $_GET['proxy'] ? trim($_GET['proxy']) : null;
    unset($_GET['proxy']);
}

$module = null;
if (isset($_GET['module'])) {
    $module = $_GET['module'] ? trim($_GET['module']) : null;
    unset($_GET['module']);
}

// Validate input
if (($proxy_name && preg_match('/\W/', $proxy_name) == 0) && ($module && preg_match('/\W/', $module) == 0)) {
    $proxy_class_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $proxy_name))) . 'Proxy';
    $proxy_fqn = sprintf('ActiveCollab\\Module\\%s\\Wires\\%s', Inflector::camelize($module), $proxy_class_name);

    $possible_paths = [
        sprintf('%s/modules/%s/Wires/%s.php', APPLICATION_PATH, $module, $proxy_class_name),
    ];

    foreach ($possible_paths as $possible_path) {
        if (!is_file($possible_path)) {
            continue;
        }

        require_once $possible_path;

        if (!class_exists($proxy_fqn, false)) {
            continue;
        }

        try {
            $reflection = new ReflectionClass($proxy_fqn);

            if (!$reflection->isSubclassOf(ProxyRequestHandler::class)) {
                continue;
            }
        } catch (Throwable $e) {
            continue;
        }

        $params = $_GET;

        if (in_array(
            $proxy_class_name,
            [
                ForwardPreviewProxy::class,
                ForwardThumbnailProxy::class,
                DownloadFileProxy::class,
                DownloadAttachmentsArchiveProxy::class,
            ]
        )) {
            $params['time'] = time();
        }

        /** @var ProxyRequestHandler $proxy */
        $proxy = new $proxy_fqn($params);
        $proxy->execute();

        break;
    }
}

// Handler not found
header('HTTP/1.0 404 Not Found');
