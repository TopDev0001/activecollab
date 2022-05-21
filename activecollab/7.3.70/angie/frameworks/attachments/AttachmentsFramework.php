<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Modules\AngieFramework;

class AttachmentsFramework extends AngieFramework
{
    const NAME = 'attachments';
    const PATH = __DIR__;

    protected string $name = 'attachments';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            [
                Attachment::class,
                LocalAttachment::class,
                WarehouseAttachment::class,
                GoogleDriveAttachment::class,
                DropboxAttachment::class,
            ],
            function (array $ids): ?iterable
            {
                return Attachments::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                IAttachments::class => __DIR__ . '/models/IAttachments.class.php',
                IAttachmentsImplementation::class => __DIR__ . '/models/IAttachmentsImplementation.class.php',

                IFile::class => __DIR__ . '/models/IFile.class.php',
                IFileImplementation::class => __DIR__ . '/models/IFileImplementation.class.php',

                FwAttachment::class => __DIR__ . '/models/attachments/FwAttachment.class.php',
                FwAttachments::class => __DIR__ . '/models/attachments/FwAttachments.class.php',

                FwThumbnails::class => __DIR__ . '/models/FwThumbnails.class.php',

                IRemoteFile::class => __DIR__ . '/models/IRemoteFile.class.php',
                IRemoteFileImplementation::class => __DIR__ . '/models/IRemoteFileImplementation.class.php',
                IWarehouseFileImplementation::class => __DIR__ . '/models/IWarehouseFileImplementation.class.php',
                IGoogleDriveFileImplementation::class => __DIR__ . '/models/IGoogleDriveFileImplementation.class.php',
                IDropboxFileImplementation::class => __DIR__ . '/models/IDropboxFileImplementation.class.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_object_from_notification_context');
        $this->listen('on_reset_manager_states');
    }
}
