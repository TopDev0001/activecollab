<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Files\Utils\FileDuplicator;

use AngieApplication;
use IFile;
use Integrations;
use LocalFile;
use WarehouseFile;
use WarehouseIntegration;

class FileDuplicator implements FileDuplicatorInterface
{
    public function duplicate(IFile $file, string $file_type = null): ?string
    {
        $file_type = $file_type ?? get_class($file);

        if ($file_type === LocalFile::class) {
            $file_path = AngieApplication::fileLocationToPath($file->getLocation());
            if (is_file($file_path)) {
                return AngieApplication::storeFile($file_path)[1];
            }
        } elseif ($file_type === WarehouseFile::class) {
            /* @var WarehouseIntegration $warehouse_integration */
            $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);
            $new_file = $warehouse_integration
                ->getFileApi()
                ->duplicateFile($warehouse_integration->getStoreId(), $file->getLocation());

            return $new_file->getLocation();
        }

        return null;
    }
}
