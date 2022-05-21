<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Files;

use AngieApplication;
use AngieModule;
use DataObjectPool;
use DropboxFile;
use File;
use Files;
use GoogleDriveFile;
use LocalFile;
use ProjectFilesAndAttachmentsCollection;
use RemoteFile;
use WarehouseFile;

class FilesModule extends AngieModule
{
    const NAME = 'files';
    const PATH = __DIR__;

    protected string $name = 'files';
    protected string $version = '5.0';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            [
                File::class,
                LocalFile::class,
                WarehouseFile::class,
                GoogleDriveFile::class,
                DropboxFile::class,
            ],
            function (array $ids): ?iterable
            {
                return Files::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        require_once __DIR__ . '/resources/autoload_model.php';

        AngieApplication::setForAutoload(
            [
                ProjectFilesAndAttachmentsCollection::class => __DIR__ . '/models/ProjectFilesAndAttachmentsCollection.class.php',
                LocalFile::class => __DIR__ . '/models/file_types/local/LocalFile.php',
                RemoteFile::class => __DIR__ . '/models/file_types/remote/RemoteFile.php',
                WarehouseFile::class => __DIR__ . '/models/file_types/remote/WarehouseFile.php',
                GoogleDriveFile::class => __DIR__ . '/models/file_types/remote/GoogleDriveFile.php',
                DropboxFile::class => __DIR__ . '/models/file_types/remote/DropboxFile.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_rebuild_activity_logs');
        $this->listen('on_reset_manager_states');
        $this->listen('on_trash_sections');
        $this->listen('on_initial_settings');
    }
}
