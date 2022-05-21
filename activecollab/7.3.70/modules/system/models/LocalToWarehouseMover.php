<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\UploadLocalAttachmentToWarehouse;
use ActiveCollab\ActiveCollabJobs\Jobs\Instance\UploadLocalFileToWarehouse;
use ActiveCollab\JobsQueue\Jobs\JobInterface;

class LocalToWarehouseMover
{
    /**
     * Move bad local attachments to warehouse.
     */
    public function moveFilesToWarehouse()
    {
        /** @var WarehouseIntegration $warehouse_integration */
        $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

        if ($warehouse_integration->isInUse()) {
            $attachments = Attachments::find(
                [
                    'conditions' => ['type = ?', LocalAttachment::class],
                    'limit' => 50,
                ]
            );

            if ($attachments) {
                foreach ($attachments as $attachment) {
                    $this->moveFileToWarehouse($attachment, $warehouse_integration);
                }
            }

            $files = Files::find(
                [
                    'conditions' => ['type = ?', LocalFile::class],
                    'limit' => 50,
                ]
            );

            if ($files) {
                foreach ($files as $file) {
                    $this->moveFileToWarehouse($file, $warehouse_integration);
                }
            }
        }
    }

    /**
     * @param LocalAttachment|LocalFile $file
     */
    private function moveFileToWarehouse($file, WarehouseIntegration $warehouse_integration)
    {
        $job_properties = [
            'priority' => JobInterface::NOT_A_PRIORITY,
            'instance_id' => AngieApplication::getAccountId(),
            'instance_type' => 'feather',
            'tasks_path' => ENVIRONMENT_PATH . '/tasks',
            'access_token' => $warehouse_integration->getAccessToken(),
            'store_id' => $warehouse_integration->getStoreId(),
        ];

        if (file_exists($file->getPath())) {
            if ($file instanceof LocalFile) {
                $job_type = UploadLocalFileToWarehouse::class;
                $job_properties['local_file_id'] = $file->getId();

                $file_identification_property = [
                    'instance_id' => AngieApplication::getAccountId(),
                    'local_file_id' => $file->getId(),
                ];
            } elseif ($file instanceof LocalAttachment) {
                $job_type = UploadLocalAttachmentToWarehouse::class;
                $job_properties['local_attachment_id'] = $file->getId();

                $file_identification_property = [
                    'instance_id' => AngieApplication::getAccountId(),
                    'local_attachment_id' => $file->getId(),
                ];
            } else {
                return;
            }

            if (!AngieApplication::jobs()->exists($job_type, $file_identification_property)) {
                AngieApplication::jobs()->dispatch(new $job_type($job_properties));
            }
        } else {
            AngieApplication::log()->notice(
                "File '{file}' doesn't exists in upload directory of #{account_id} account",
                [
                    'type' => get_class($file),
                    'file' => $file->getName(),
                    'path' => $file->getPath(),
                ]
            );
        }
    }
}
