<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Wires\DownloadFileProxy;
use ActiveCollab\Module\System\Wires\ForwardPreviewProxy;

/**
 * Framework level attachments manager implementation.
 *
 * @package angie.frameworks.attachments
 * @subpackage models
 */
abstract class FwAttachments extends BaseAttachments
{
    /**
     * Delete records from attachments table that match given $conditions.
     *
     * This function also deletes all files from /upload folder so this function
     * is not 100% transaction safe
     *
     * @param  mixed     $conditions
     * @return bool
     * @throws Exception
     */
    public static function delete($conditions = null)
    {
        try {
            DB::beginWork('Deleting attachments @ ' . __CLASS__);

            $perpared_conditions = DB::prepareConditions($conditions);
            $where_string = trim($perpared_conditions) == '' ? '' : "WHERE $perpared_conditions";

            $rows = DB::execute("SELECT id, location, type FROM attachments $where_string");
            if (is_foreachable($rows)) {
                // create id => location map
                $attachments = [];
                foreach ($rows as $row) {
                    $attachments[(int) $row['id']] = $row['location'];
                }

                // get attachment ids
                $attachment_ids = array_keys($attachments);

                // delete attachments themselves
                DB::execute('DELETE FROM attachments WHERE id IN (?)', $attachment_ids);

                // delete attachments from disk
                foreach ($attachments as $attachment) {
                    if ($attachment['location']) {
                        AngieApplication::storage()->deleteFile($attachment['type'], $attachment['location']);
                    }
                }
            }

            DB::commit('Attachments deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete attachments @ ' . __CLASS__);

            throw $e;
        }

        return true;
    }

    /**
     * Drop records by parents.
     *
     * @param array|null $parents
     */
    public static function deleteByParents($parents)
    {
        if ($conditions = static::typeIdsMapToConditions($parents)) {
            /** @var Attachment[] $attachments */
            if ($attachments = static::findBySQL('SELECT * FROM attachments WHERE ' . $conditions)) {
                foreach ($attachments as $attachment) {
                    $attachment->delete();
                }
            }
        }
    }

    /**
     * @var array
     */
    private static $details_by_parent = [];

    /**
     * Preload attachment details for a given type and a list of ID-s.
     *
     * @param string $parent_type
     */
    public static function preloadDetailsByParents($parent_type, array $parent_ids)
    {
        self::$details_by_parent[$parent_type] = [];

        if ($rows = DB::execute('SELECT id, parent_id, name, type, mime_type, size, location, md5, created_on, created_by_id, disposition, raw_additional_properties FROM attachments WHERE parent_type = ? AND parent_id IN (?) ORDER BY name', $parent_type, $parent_ids)) {
            $rows->setCasting([
                'size' => DBResult::CAST_INT,
                'created_on' => DBResult::CAST_DATETIME,
            ]);

            foreach ($rows as $row) {
                if (empty(self::$details_by_parent[$parent_type][$row['parent_id']])) {
                    self::$details_by_parent[$parent_type][$row['parent_id']] = [];
                }

                [
                    $thumbnail_url,
                    $download_url,
                    $preview_url,
                ] = self::prepareUrlsFromRow($row);

                $data = [
                    'id' => $row['id'],
                    'class' => $row['type'],
                    'name' => $row['name'],
                    'mime_type' => $row['mime_type'],
                    'size' => $row['size'],
                    'disposition' => $row['disposition'],
                    'created_on' => $row['created_on'],
                    'created_by_id' => $row['created_by_id'],
                    'thumbnail_url' => $thumbnail_url,
                    'download_url' => $download_url,
                    'preview_url' => $preview_url,
                ];

                self::$details_by_parent[$parent_type][$row['parent_id']][$row['id']] = $data;
            }
        }

        if ($zeros = array_diff($parent_ids, array_keys(self::$details_by_parent[$parent_type]))) {
            foreach ($zeros as $parent_with_no_labels) {
                self::$details_by_parent[$parent_type][$parent_with_no_labels] = [];
            }
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$details_by_parent = [];
    }

    /**
     * @param  DataObject|IAttachments $parent
     * @return array
     */
    public static function getDetailsByParent(IAttachments $parent)
    {
        $parent_type = get_class($parent);
        $parent_id = $parent->getId();

        if (isset(self::$details_by_parent[$parent_type][$parent_id])) {
            return array_values(self::$details_by_parent[$parent_type][$parent_id]);
        } else {
            $result = [];

            if ($rows = DB::execute('SELECT id, parent_id, name, type, mime_type, size, location, md5, created_on, created_by_id, disposition, raw_additional_properties FROM attachments WHERE parent_type = ? AND parent_id IN (?) ORDER BY name', $parent_type, $parent_id)) {
                $rows->setCasting([
                    'size' => DBResult::CAST_INT,
                    'created_on' => DBResult::CAST_DATETIME,
                ]);

                foreach ($rows as $row) {
                    [
                        $thumbnail_url,
                        $download_url,
                        $preview_url,
                    ] = self::prepareUrlsFromRow($row);

                    $result[] = [
                        'id' => $row['id'],
                        'class' => $row['type'],
                        'name' => $row['name'],
                        'mime_type' => $row['mime_type'],
                        'size' => $row['size'],
                        'disposition' => $row['disposition'],
                        'created_on' => $row['created_on'],
                        'created_by_id' => $row['created_by_id'],
                        'thumbnail_url' => $thumbnail_url,
                        'download_url' => $download_url,
                        'preview_url' => $preview_url,
                    ];
                }
            }

            return $result;
        }
    }

    /**
     * Return inline image details by parent.
     *
     * @param  int         $id
     * @param  string|null $parent_type
     * @param  int|null    $parent_id
     * @return array
     */
    public static function getInlineImageDetailsByParent($id, $parent_type = null, $parent_id = null)
    {
        if (isset(self::$details_by_parent[$parent_type][$parent_id][$id])) {
            return self::$details_by_parent[$parent_type][$parent_id][$id];
        }

        $row = DB::executeFirstRow('SELECT
                `id`,
                `parent_id`,
                `name`,
                `type`,
                `mime_type`,
                `size`,
                `location`,
                `md5`,
                `created_on`,
                `created_by_id`,
                `disposition`,
                `raw_additional_properties`
            FROM `attachments`
            WHERE `id` = ?',
            $id
        );

        if ($row) {
            $row['size'] = (int) $row['size'];
            $row['created_on'] = DateTimeValue::makeFromString($row['created_on']);

            [
                $thumbnail_url,
                $download_url,
                $preview_url,
            ] = self::prepareUrlsFromRow($row);

            return [
                'id' => $row['id'],
                'class' => $row['type'],
                'name' => $row['name'],
                'mime_type' => $row['mime_type'],
                'size' => $row['size'],
                'disposition' => $row['disposition'],
                'created_on' => $row['created_on'],
                'created_by_id' => $row['created_by_id'],
                'thumbnail_url' => $thumbnail_url,
                'download_url' => $download_url,
                'preview_url' => $preview_url,
            ];
        }

        return [];
    }

    /**
     * Return prepared urls from row data.
     *
     * @param  array $row
     * @return array
     */
    protected static function prepareUrlsFromRow($row)
    {
        $proxy_data = [
            'context' => 'attachments',
            'id' => $row['id'],
            'size' => $row['size'],
            'md5' => $row['md5'],
            'timestamp' => $row['created_on'] instanceof DateTimeValue ? $row['created_on']->toMySQL() : '',
            'force' => true,
        ];

        $thumbnail_url = Thumbnails::getUrl(
            AngieApplication::fileLocationToPath($row['location']),
            $row['location'],
            $row['name'],
            '--WIDTH--',
            '--HEIGHT--',
            '--SCALE--'
        );
        $download_url = AngieApplication::getProxyUrl(
            DownloadFileProxy::class,
            AttachmentsFramework::INJECT_INTO,
            $proxy_data
        );
        $preview_url = AngieApplication::getProxyUrl(
            ForwardPreviewProxy::class,
            AttachmentsFramework::INJECT_INTO,
            $proxy_data
        );

        if ($row['type'] == WarehouseAttachment::class) {
            // NOTE: for WH files, location and md5 values can be stored in additional properties (case when create recurring tasks and job is not executed)
            $file_id = $row['location'] ? $row['location'] : unserialize($row['raw_additional_properties'])['location'];
            $hash = $row['md5'] ? $row['md5'] : unserialize($row['raw_additional_properties'])['md5'];

            $warehouse_integration = self::getWarehouseIntegration();

            $thumbnail_url = $warehouse_integration->prepareFileThumbnailUrl(
                $file_id,
                $hash,
                '--WIDTH--',
                '--HEIGHT--'
            );
            $download_url = $warehouse_integration->prepareFileDownloadUrl($file_id, $hash);
            $preview_url = $warehouse_integration->prepareFilePreviewUrl($file_id, $hash);
        } elseif ($row['type'] == GoogleDriveAttachment::class || $row['type'] == DropboxAttachment::class) {
            $additional_properties = !empty($row['raw_additional_properties']) ? unserialize($row['raw_additional_properties']) : [];

            $thumbnail_url = null;
            $download_url = null;
            $preview_url = !empty($additional_properties['url']) ? $additional_properties['url'] : null;
        }

        return [
            $thumbnail_url,
            $download_url,
            $preview_url,
        ];
    }

    /**
     * @var WarehouseIntegration
     */
    private static $warehouse_integration = false;

    private static function getWarehouseIntegration()
    {
        if (self::$warehouse_integration === false) {
            self::$warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);
        }

        return self::$warehouse_integration;
    }
}
