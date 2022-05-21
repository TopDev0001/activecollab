<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Expander;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Urls\Expander\Services\Warehouse\WarehouseFileDetails;
use ActiveCollab\Foundation\Urls\Expander\Services\Warehouse\WarehouseFileDetailsInterface;
use ActiveCollab\Foundation\Urls\Services\WarehouseUrl;
use ActiveCollab\Foundation\Urls\Services\WarehouseUrlInterface;
use DBConnection;
use Psr\Log\LoggerInterface;
use simple_html_dom;
use simple_html_dom_node;
use WarehouseAttachment;
use WarehouseFile;

class UrlExpander implements UrlExpanderInterface
{
    private DBConnection $connection;
    private LoggerInterface $logger;

    public function __construct(DBConnection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function expandUrlsInDom(
        simple_html_dom $dom,
        IdentifiableInterface $context,
        string $display
    )
    {
        /** @var simple_html_dom_node[] $elements */
        $elements = $dom->find(sprintf('a[href^=%s]', WarehouseUrlInterface::WAREHOUSE_DOMAIN));

        if ($elements) {
            foreach ($elements as $element) {
                $href = $element->getAttribute('href');

                $this->logger->info(
                    'Warehouse link expansion: {warehouse_url} detected',
                    [
                        'warehouse_url' => $href,
                    ]
                );

                if (!filter_var($href, FILTER_VALIDATE_URL)) {
                    $this->logger->info(
                        'Warehouse link expansion: Skipping {warehouse_url}, invalid link',
                        [
                            'warehouse_url' => $href,
                        ]
                    );

                    continue;
                }

                $warehouse_url = new WarehouseUrl($href, WarehouseUrlInterface::WAREHOUSE_DOMAIN);

                if (!$warehouse_url->isFile()) {
                    $this->logger->info(
                        'Warehouse link expansion: Skipping {warehouse_url}, not a Warehouse file link',
                        [
                            'warehouse_url' => $href,
                        ]
                    );

                    continue;
                }

                $warehouse_file_details = $this->getWarehouseFileDetails(
                    $warehouse_url->getFileLocation(),
                    $warehouse_url->getFileMd5Hash()
                );

                if (!$warehouse_file_details instanceof WarehouseFileDetailsInterface) {
                    $this->logger->info(
                        'Warehouse link expansion: Skipping {warehouse_url}, cannot find link under attachments or files',
                        [
                            'warehouse_url' => $href,
                        ]
                    );

                    continue;
                }

                $this->logger->info(
                    'Warehouse link expansion: Replacing {warehouse_url} with {warehouse_file_name}',
                    [
                        'warehouse_url' => $href,
                        'warehouse_file_name' => $warehouse_file_details->getFileName(),
                    ]
                );

                $element->outertext = sprintf(
                    '<a href="%s">%s</a> (%s)',
                    $warehouse_url->getExtendedUrl($this->getUrlExtensionByAction($warehouse_url->getFileAction())),
                    $warehouse_file_details->getFileName(),
                    format_file_size($warehouse_file_details->getFileSize())
                );
            }
        }
    }

    private function getUrlExtensionByAction(string $action): array
    {
        return [
            'intent' => WarehouseUrlInterface::FILE_ACTION_EXTENSIONS[$action] ?? WarehouseUrlInterface::FILE_ACTION_DOWNLOAD,
        ];
    }

    private function getWarehouseFileDetails(
        string $location,
        string $md5_hash
    ): ?WarehouseFileDetailsInterface
    {
        $row = $this->connection->executeFirstRow(
            'SELECT `name`, `size` FROM `attachments` WHERE `type` = ? AND `location` = ? AND `md5` = ?',
            [
                WarehouseAttachment::class,
                $location,
                $md5_hash,
            ]
        );

        if (empty($row)) {
            $row = $this->connection->executeFirstRow(
                'SELECT `name`, `size` FROM `files` WHERE `type` = ? AND `location` = ? AND `md5` = ?',
                [
                    WarehouseFile::class,
                    $location,
                    $md5_hash,
                ]
            );
        }

        if (is_array($row) && !empty($row['name'])) {
            return new WarehouseFileDetails($row['name'], (int) $row['size']);
        }

        return null;
    }
}
