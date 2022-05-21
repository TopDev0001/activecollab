<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\AttachmentsArchive;

use ActiveCollab\Module\System\Wires\DownloadAttachmentsArchiveProxy;
use AngieApplication;
use Attachment;
use AttachmentsFramework;
use DateTimeValue;
use GoogleDriveAttachment;
use IAttachments;
use PclZip;
use RuntimeException;

class AttachmentsArchive implements AttachmentsArchiveInterface
{
    private IAttachments $parent;
    protected string $archive_id;

    public function __construct(IAttachments $parent)
    {
        $this->parent = $parent;
        $this->archive_id = md5(
            implode('-',
                [
                    AngieApplication::authentication()->getLoggedUser()->getId(),
                    DateTimeValue::now()->getTimestamp(),
                    uniqid(),
                ]
            )
        );
    }

    public function getArchiveId(): string
    {
        return $this->archive_id;
    }

    public function jsonSerialize(): array
    {
        return [
            'download_url' => $this->prepareDownloadUrl(true),
        ];
    }

    public function getPath(): string
    {
        return AngieApplication::getAvailableWorkFileName(
            sprintf('attachments-archive-%s', $this->archive_id),
            null,
            false
        );
    }

    public function prepareForDownload(): AttachmentsArchive
    {
        $archive_path = AngieApplication::getAvailableWorkFileName(
            uniqid('--PREPARING--') . '-' . DateTimeValue::now()->getTimestamp()
        );
        $work_dir = AngieApplication::getAvailableDirName(WORK_PATH, 'batch_download_attachments');

        $paths = [];

        /** @var Attachment $attachment */
        foreach ($this->parent->getAttachments() as $attachment) {
            if ($attachment instanceof GoogleDriveAttachment) {
                continue;
            }

            if ($attachment->getDisposition() === IAttachments::ATTACHMENT) {
                $count = 1;
                $path_to_file = $work_dir . '/' . $attachment->getName();
                $path_parts = pathinfo($path_to_file);

                while (in_array($path_to_file, $paths)) {
                    $path_to_file = "$path_parts[dirname]/$path_parts[filename]($count).$path_parts[extension]";
                    ++$count;
                }

                $paths[] = $path_to_file;
                copy($attachment->getPath(), $path_to_file);
            }
        }

        $archive = new PclZip($archive_path);
        $v_list = $archive->create(implode(',', $paths), PCLZIP_OPT_REMOVE_PATH, $work_dir);

        delete_dir($work_dir);

        if ($v_list == 0) {
            throw new RuntimeException($archive->errorInfo(true));
        }

        rename($archive_path, $this->getPath());

        return $this;
    }

    private function getMd5(): ?string
    {
        $filepath = $this->getPath();

        if (is_file($filepath)) {
            return md5_file($filepath);
        }

        return null;
    }

    private function prepareDownloadUrl(bool $force = false): string
    {
        return AngieApplication::getProxyUrl(
            DownloadAttachmentsArchiveProxy::class,
            AttachmentsFramework::INJECT_INTO,
            [
                'id' => $this->getArchiveId(),
                'md5' => $this->getMd5(),
                'parent_type' => strtolower(get_class($this->parent)),
                'parent_id' => $this->parent->getId(),
                'force' => $force,
            ]
        );
    }
}
