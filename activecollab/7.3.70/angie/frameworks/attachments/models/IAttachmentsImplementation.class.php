<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\UploadLocalAttachmentToWarehouse;
use ActiveCollab\ActiveCollabJobs\Jobs\Instance\UploadWarehouseAttachmentToWarehouse;
use Angie\Search\SearchItem\SearchItemInterface as SearchItem;

trait IAttachmentsImplementation
{
    /**
     * @var array
     */
    private $attach_uploaded_files_on_save = [];

    /**
     * @var array
     */
    private $drop_attached_files_on_save = [];

    /**
     * Say hello to the parent object.
     */
    public function registerIAttachmentsImplementation(): void
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['attachments'] = Attachments::getDetailsByParent($this);
            }
        );

        $this->registerEventHandler(
            'on_set_attribute',
            function ($attribute, $value) {
                if ($attribute == 'attach_uploaded_files' && is_array($value)) {
                    $this->attach_uploaded_files_on_save = $value;
                } elseif ($attribute == 'drop_attached_files' && is_array($value)) {
                    $this->drop_attached_files_on_save = $value;
                }
            }
        );

        $this->registerEventHandler(
            'on_after_save',
            function ($is_new) {
                $changes = 0;

                if ($this->attach_uploaded_files_on_save && is_foreachable($this->attach_uploaded_files_on_save)) {
                    if ($files = UploadedFiles::findByCodes($this->attach_uploaded_files_on_save)) {
                        DB::transact(function () use ($files, &$changes) {
                            foreach ($files as $file) {
                                $this->attachUploadedFile($file);
                                ++$changes;
                            }
                        }, 'Attaching files');
                    }
                }

                if ($this->drop_attached_files_on_save && is_foreachable($this->drop_attached_files_on_save)) {
                    /** @var Attachment[] $attachments */
                    $attachments = Attachments::findBySQL('SELECT * FROM attachments WHERE ' . Attachments::parentToCondition($this) . ' AND id IN (?)', $this->drop_attached_files_on_save);

                    if ($attachments) {
                        DB::transact(function () use ($attachments, &$changes) {
                            foreach ($attachments as $attachment) {
                                $attachment->preventTouchOnNextDelete();
                                $attachment->delete(true);
                                ++$changes;
                            }
                        }, 'Dropping attachments');
                    }
                }

                if (empty($is_new) && $changes && ($item_to_update = $this->updateSearchItemOnAttachmentsChange())) {
                    AngieApplication::search()->update($item_to_update);
                }
            }
        );

        $this->registerEventHandler(
            'on_before_delete',
            function () {
                if ($attachments = $this->getAttachments()) {
                    foreach ($attachments as $attachment) {
                        $attachment->delete(true);
                    }
                }
            }
        );
    }

    /**
     * Returns true if there are files attached to this object.
     *
     * @return bool
     */
    public function hasAttachments()
    {
        return (bool) $this->countAttachments();
    }

    /**
     * Return file attachments.
     *
     * @return DataObject[]|Attachment[]
     */
    public function getAttachments()
    {
        return Attachments::find(
            [
                'conditions' => Attachments::parentToCondition($this),
            ]
        );
    }

    /**
     * Return object's inline attachments (files attached to object's body text).
     *
     * @return DataObject[]|Attachment[]
     */
    public function getInlineAttachments()
    {
        return Attachments::find(
            [
                'conditions' => DB::prepare(Attachments::parentToCondition($this) . ' AND disposition = ?', IAttachments::INLINE),
            ]
        );
    }

    /**
     * Return number of files attached to parent object.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countAttachments($use_cache = true)
    {
        return AngieApplication::cache()->getByObject($this, 'attachments_count', function () {
            return DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM attachments WHERE ' . Attachments::parentToCondition($this));
        }, empty($use_cache));
    }

    /**
     * Returns true if we have attachment updates to save.
     *
     * @return bool
     */
    protected function hasAttachmentUpdatesToSave()
    {
        return count($this->attach_uploaded_files_on_save) || count($this->drop_attached_files_on_save);
    }

    // ---------------------------------------------------
    //  Attach file(s)
    // ---------------------------------------------------

    /**
     * Attach file from file system.
     *
     * If $name and/or $type are missing they will be extracted from real file
     *
     * If $commit is TRUE, pending files will be commited
     *
     * @param  string       $path
     * @param  string       $filename
     * @param  string       $mime_type
     * @param  User         $user
     * @return Attachment
     * @throws FileDnxError
     */
    public function attachFile($path, $filename = null, $mime_type = 'application/octet-stream', $user = null)
    {
        if (is_file($path)) {
            [$target_path, $location] = AngieApplication::storeFile($path);

            if (empty($filename)) {
                $filename = basename($path);
            }

            $properties = ['type' => 'LocalAttachment', 'parent_type' => get_class($this), 'parent_id' => $this->getId(), 'name' => $filename, 'mime_type' => $mime_type, 'size' => filesize($path), 'location' => $location, 'md5' => md5_file($target_path)];

            if ($user instanceof IUser) {
                $properties['created_by_id'] = $user->getId();
                $properties['created_by_name'] = $user->getName();
                $properties['created_by_email'] = $user->getEmail();
            }

            $attachment = Attachments::create($properties);

            if ($attachment instanceof Attachment) {
                AngieApplication::cache()->removeByObject($this, 'attachments_count');
            }

            return $attachment;
        }

        throw new FileDnxError($path);
    }

    /**
     * Attach warehouse file.
     *
     * @return WarehouseAttachment
     * @throws Exception
     */
    public function attachWarehouseFile(WarehouseAttachment $attachment, User $user = null, IAttachments $parent_to = null)
    {
        try {
            DB::beginWork('Begin: attaching warehouse file @ ' . __CLASS__);

            /** @var WarehouseAttachment $new_attachment */
            $new_attachment = $attachment->copy();

            $parent_id = $parent_to ? $parent_to->getId() : $this->getId();
            $parent_type = $parent_to ? get_class($parent_to) : get_class($this);

            $new_attachment->setParentId($parent_id);
            $new_attachment->setParentType($parent_type);
            $new_attachment->setRawAdditionalProperties(serialize([
                'is_temp' => true,
            ]));

            if ($user instanceof IUser) {
                $new_attachment->setCreatedBy($user);
            }

            $new_attachment->save();

            /** @var WarehouseIntegration $warehouse_integration */
            $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

            $job = new UploadWarehouseAttachmentToWarehouse([
                'instance_id' => AngieApplication::getAccountId(),
                'instance_type' => 'feather',
                'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                'access_token' => $warehouse_integration->getAccessToken(),
                'store_id' => $warehouse_integration->getStoreId(),
                'warehouse_attachment_id' => $new_attachment->getId(),
                'location' => $attachment->getLocation(),
            ]);

            AngieApplication::jobs()->dispatch($job);

            DB::commit('Done: attaching warehouse file @ ' . __CLASS__);

            AngieApplication::cache()->removeByObject($this, 'attachments_count');

            return $new_attachment;
        } catch (Exception $e) {
            DB::rollback('Rollback: attaching warehouse file @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Attach google drive or dropbox file.
     *
     * @return DataObject
     * @throws Exception
     */
    public function attachExternalFile(Attachment $attachment, User $user = null, IAttachments $parent_to = null)
    {
        try {
            DB::beginWork('Begin: attaching external file @ ' . __CLASS__);

            /** @var Attachment $new_attachment */
            $new_attachment = $attachment->copy();

            $parent_id = $parent_to ? $parent_to->getId() : $this->getId();
            $parent_type = $parent_to ? get_class($parent_to) : get_class($this);

            $new_attachment->setParentId($parent_id);
            $new_attachment->setParentType($parent_type);
            $new_attachment->setLocation(null);
            $new_attachment->setMd5(null);

            if (method_exists($attachment, 'getUrl')) {
                $new_attachment->setRawAdditionalProperties(serialize([
                    'url' => $attachment->getUrl(),
                ]));
            }

            if ($user instanceof IUser) {
                $new_attachment->setCreatedBy($user);
            }

            $new_attachment->save();

            DB::commit('Done: attaching external file @ ' . __CLASS__);

            AngieApplication::cache()->removeByObject($this, 'attachments_count');

            return $new_attachment;
        } catch (Exception $e) {
            DB::rollback('Rollback: attaching external file @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Attach uploaded file.
     *
     * @param  string     $disposition
     * @return Attachment
     * @throws Exception
     */
    public function attachUploadedFile(UploadedFile & $uploaded_file, $disposition = IAttachments::ATTACHMENT)
    {
        try {
            DB::beginWork('Begin: attaching uploaded file @ ' . __CLASS__);

            $attributes = [
                'type' => str_replace('UploadedFile', '', get_class($uploaded_file)) . 'Attachment',
                'parent_type' => get_class($this),
                'parent_id' => $this->getId(),
                'name' => $uploaded_file->getName(),
                'mime_type' => $uploaded_file->getMimeType(),
                'size' => $uploaded_file->getSize(),
                'location' => $uploaded_file->getLocation(),
                'md5' => $uploaded_file->getMd5(),
                'disposition' => $disposition,
                'created_on' => $uploaded_file->getCreatedOn(),
                'created_by_id' => $uploaded_file->getCreatedById(),
                'created_by_name' => $uploaded_file->getCreatedByName(),
                'created_by_email' => $uploaded_file->getCreatedByEmail(),
                'raw_additional_properties' => serialize($uploaded_file->getAdditionalProperties()),
            ];

            if ($uploaded_file instanceof WarehouseUploadedFile) {
                $attributes['search_content'] = $uploaded_file->getTikaData();
            }

            $attachment = Attachments::create($attributes);

            if ($attachment instanceof Attachment) {
                $uploaded_file->keepFileOnDelete(true);
                $uploaded_file->delete();
            }

            if ($attachment instanceof LocalAttachment) {
                /** @var WarehouseIntegration $warehouse_integration */
                $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

                if ($warehouse_integration->isInUse()) {
                    $job = new UploadLocalAttachmentToWarehouse([
                        'instance_id' => AngieApplication::getAccountId(),
                        'instance_type' => 'feather',
                        'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                        'access_token' => $warehouse_integration->getAccessToken(),
                        'store_id' => $warehouse_integration->getStoreId(),
                        'local_attachment_id' => $attachment->getId(),
                    ]);

                    AngieApplication::jobs()->dispatch($job);
                }
            }

            DB::commit('Done: attaching uploaded file @ ' . __CLASS__);

            AngieApplication::cache()->removeByObject($this, 'attachments_count');

            return $attachment;
        } catch (Exception $e) {
            DB::rollback('Rollback: attaching uploaded file @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Attach files from array.
     *
     * $from keys are:
     *
     * - path
     * - filename
     * - type
     *
     * @param  array $from
     * @return int
     */
    public function attachFilesFromArray($from)
    {
        $attached = 0;

        if ($from && is_foreachable($from)) {
            foreach ($from as $file) {
                $this->attachFile($file['path'], $file['filename'], $file['type']);
                ++$attached;
            }
        }

        return $attached;
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------
    /**
     * Clone attachments to a given object.
     *
     * @throws Exception
     * @throws InvalidInstanceError
     */
    public function cloneAttachmentsTo(IAttachments $to)
    {
        $from_class = get_class($this);
        $from_id = $this->getId();

        if ($to instanceof IAttachments) {
            $to_class = get_class($to);
            $to_id = $to->getId();
        } else {
            throw new InvalidInstanceError('to', $to, 'IAttachments');
        }

        $rows = DB::execute(
            'SELECT `id`, `type`, `name`, `mime_type`, `size`, `location`, `md5`, `created_on`, `created_by_id`, `created_by_name`, `created_by_email`, `raw_additional_properties`, `is_hidden_from_clients`
                FROM `attachments`
                WHERE `parent_type` = ? AND `parent_id` = ?',
            $from_class,
            $from_id
        );

        if ($rows) {
            try {
                DB::beginWork('Begin:  @ ' . __CLASS__);

                $context = $to instanceof IChild ? $to->getParent() : $to;
                $project_id = $context instanceof IProjectElement ? $context->getProjectId() : 0;

                $to_class = DB::escape($to_class);
                $to_id = DB::escape($to_id);

                $to_insert = [];

                foreach ($rows as $row) {
                    $attachment = DataObjectPool::get(Attachment::class, $row['id']);

                    if (!$attachment instanceof Attachment) {
                        continue;
                    }

                    if ($attachment instanceof WarehouseAttachment) {
                        $this->attachWarehouseFile($attachment, null, $to);
                    } elseif ($attachment instanceof GoogleDriveAttachment || $attachment instanceof DropboxAttachment) {
                        $this->attachExternalFile($attachment, null, $to);
                    } else {
                        $source_path = AngieApplication::fileLocationToPath($row['location']);

                        if (is_file($source_path)) {
                            $new_location = AngieApplication::storeFile($source_path)[1];

                            $to_insert[] = DB::prepare(
                                "($project_id, $to_class, $to_id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                $row['type'],
                                $row['name'],
                                $row['mime_type'],
                                $row['size'],
                                $new_location,
                                $row['md5'],
                                $row['created_on'],
                                $row['created_by_id'],
                                $row['created_by_name'],
                                $row['created_by_email'],
                                $row['raw_additional_properties'],
                                $row['is_hidden_from_clients']
                            );
                        }
                    }
                }

                if (count($to_insert)) {
                    DB::execute('INSERT INTO attachments (project_id, parent_type, parent_id, type, name, mime_type, size, location, md5, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties, is_hidden_from_clients) VALUES ' . implode(', ', $to_insert));
                }

                DB::commit('Done:  @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Rollback:  @ ' . __CLASS__);
                throw $e;
            }
        }
    }

    /**
     * Prepare attachments to be send via CLI.
     *
     * array(
     *  array(
     *    'path' => path_to_file/image.png',
     *    'filename' => 'image.png',
     *    'type' => get_mime_type(path_to_file)
     *  ),
     * )
     *
     * @param  bool  $serialize
     * @return array
     */
    public function prepareAttachmentsForCLI($serialize = true)
    {
        if ($this->hasAttachments()) {
            $for_send = [];

            foreach ($this->getAttachments() as $attachment) {
                $for_send[] = ['path' => $attachment->getPath(), 'filename' => $attachment->getName(), 'type' => $attachment->getMimeType()];
            }
        } else {
            $for_send = null;
        }

        return $serialize ? serialize($for_send) : $for_send;
    }

    /**
     * Return parent that attachment's update requires search index update (if applicable).
     *
     * @return SearchItem|null
     */
    public function updateSearchItemOnAttachmentsChange()
    {
        $parent = $this;

        if ($parent instanceof Comment) {
            $parent = $parent->getParent();
        }

        if ($parent instanceof SearchItem) {
            if ($parent instanceof ITrash && $parent->getIsTrashed()) {
                return null;
            }

            return $parent;
        }

        return null;
    }

    // ---------------------------------------------------
    //  Expectatons
    // ---------------------------------------------------

    /**
     * Return ID of this object.
     *
     * @return int
     */
    abstract public function getId();

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
