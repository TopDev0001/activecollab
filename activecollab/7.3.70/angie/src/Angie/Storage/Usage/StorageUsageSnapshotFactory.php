<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Usage;

use Angie\Storage\ServicesManager\StorageServicesManagerInterface;
use DateTimeValue;
use DateValue;
use DB;
use DBResult;
use ProjectTemplateFile;

class StorageUsageSnapshotFactory implements StorageUsageSnapshotFactoryInterface
{
    private $storage_services_manager;

    public function __construct(StorageServicesManagerInterface $storage_services_manager)
    {
        $this->storage_services_manager = $storage_services_manager;
    }

    public function getSnapshotForDay(DateValue $day): StorageUsageSnapshotInterface
    {
        $end_of_day = $day->endOfDay();

        $usage_data_by_service = array_fill_keys(
            StorageServicesManagerInterface::SERVICES,
            [
                StorageUsageSnapshotInterface::NUMBER_OF_FILES_DATA_KEY => 0,
                StorageUsageSnapshotInterface::TOTAL_FILE_SIZE_DATA_KEY => 0,
            ]
        );

        $total_number_of_files = $this->queryFiles($end_of_day, $usage_data_by_service);
        $total_number_of_attachments = $this->queryAttachments($end_of_day, $usage_data_by_service);
        $total_number_of_uploaded_files = $this->queryUploadedFiles($end_of_day, $usage_data_by_service);
        $this->queryProjectTemplateFiles($end_of_day, $usage_data_by_service);

        return new StorageUsageSnapshot(
            $day,
            $total_number_of_files,
            $total_number_of_attachments,
            $total_number_of_uploaded_files,
            $usage_data_by_service
        );
    }

    private function queryFiles(DateTimeValue $end_of_day, array &$data): int
    {
        $total_number_of_files = 0;

        if ($file_rows = DB::execute(
            "SELECT `type`, SUM(`size`) AS 'size', COUNT(`id`) AS 'number_of_files'
                FROM `files`
                WHERE `is_trashed` = ? AND `created_on` <= ?
                GROUP BY `type`",
            false,
            $end_of_day)
        ) {
            $file_rows->setCasting(
                [
                    'size' => DBResult::CAST_INT,
                    'number_of_files' => DBResult::CAST_INT,
                ]
            );

            foreach ($file_rows as $file_row) {
                $total_number_of_files += $file_row['number_of_files'];

                $service = $this->storage_services_manager->getServiceTypeFromFileType($file_row['type']);

                $data[$service][StorageUsageSnapshotInterface::NUMBER_OF_FILES_DATA_KEY] += $file_row['number_of_files'];
                $data[$service][StorageUsageSnapshotInterface::TOTAL_FILE_SIZE_DATA_KEY] += $file_row['size'];
            }
        }

        return $total_number_of_files;
    }

    private function queryAttachments(DateTimeValue $end_of_day, array &$data): int
    {
        $total_number_of_attachments = 0;

        if ($attachment_rows = DB::execute(
            "SELECT `type`, SUM(`size`) AS 'size', COUNT(`id`) AS 'number_of_files'
                FROM `attachments`
                WHERE `created_on` <= ?
                GROUP BY `type`",
            $end_of_day)
        ) {
            $attachment_rows->setCasting(
                [
                    'size' => DBResult::CAST_INT,
                    'number_of_files' => DBResult::CAST_INT,
                ]
            );

            foreach ($attachment_rows as $attachment_row) {
                $total_number_of_attachments += $attachment_row['number_of_files'];

                $service = $this->storage_services_manager->getServiceTypeFromFileType($attachment_row['type']);

                $data[$service][StorageUsageSnapshotInterface::NUMBER_OF_FILES_DATA_KEY] += $attachment_row['number_of_files'];
                $data[$service][StorageUsageSnapshotInterface::TOTAL_FILE_SIZE_DATA_KEY] += $attachment_row['size'];
            }
        }

        return $total_number_of_attachments;
    }

    public function queryUploadedFiles(DateTimeValue $end_of_day, array &$data)
    {
        $total_number_of_uploaded_files = 0;

        if ($uploaded_file_rows = DB::execute(
            "SELECT `type`, SUM(`size`) AS 'size', COUNT(`id`) AS 'number_of_files'
                FROM `uploaded_files`
                WHERE `created_on` <= ?
                GROUP BY `type`",
            $end_of_day)
        ) {
            $uploaded_file_rows->setCasting(
                [
                    'size' => DBResult::CAST_INT,
                    'number_of_files' => DBResult::CAST_INT,
                ]
            );

            foreach ($uploaded_file_rows as $uploaded_file_row) {
                $total_number_of_uploaded_files += $uploaded_file_row['number_of_files'];

                $service = $this->storage_services_manager->getServiceTypeFromFileType($uploaded_file_row['type']);

                $data[$service][StorageUsageSnapshotInterface::NUMBER_OF_FILES_DATA_KEY] += $uploaded_file_row['number_of_files'];
                $data[$service][StorageUsageSnapshotInterface::TOTAL_FILE_SIZE_DATA_KEY] += $uploaded_file_row['size'];
            }
        }

        return $total_number_of_uploaded_files;
    }

    private function queryProjectTemplateFiles(DateTimeValue $end_of_day, array &$data)
    {
        if ($project_template_file_rows = DB::execute(
            'SELECT id, raw_additional_properties
                FROM project_template_elements
                WHERE `type` = ? AND `created_on` <= ?',
            ProjectTemplateFile::class,
            $end_of_day
        )) {
            foreach ($project_template_file_rows as $project_template_file_row) {
                $unserialized_properties = unserialize($project_template_file_row['raw_additional_properties']);

                if (!empty($unserialized_properties['type'])) {
                    $service = $this->storage_services_manager->getServiceTypeFromFileType($unserialized_properties['type']);

                    $data[$service][StorageUsageSnapshotInterface::NUMBER_OF_FILES_DATA_KEY]++;

                    if (!empty($unserialized_properties['size'])) {
                        $data[$service][StorageUsageSnapshotInterface::TOTAL_FILE_SIZE_DATA_KEY] += (int) $unserialized_properties['size'];
                    }
                }
            }
        }
    }
}
