<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class FwDropboxIntegration extends Integration
{
    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return $this->getAppKey() && $this->getAppSecret();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Dropbox';
    }

    public function getShortName(): string
    {
        return 'dropbox';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Attach files from your Dropbox');
    }

    /**
     * Return dropbox app key.
     *
     * @return string
     */
    public function getAppKey()
    {
        return defined('DROPBOX_APP_KEY') && AngieApplication::isOnDemand() ? DROPBOX_APP_KEY : $this->getAdditionalProperty('app_key');
    }

    /**
     * Set dropbox app key.
     *
     * @param  string $value
     * @return string
     */
    public function setAppKey($value)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('app_key', $value);
        }

        return $this->getAppKey();
    }

    /**
     * Return dropbox app secret.
     *
     * @return string
     */
    public function getAppSecret()
    {
        return defined('DROPBOX_APP_SECRET') && AngieApplication::isOnDemand() ? DROPBOX_APP_SECRET : $this->getAdditionalProperty('app_secret');
    }

    /**
     * Set dropbox app secret.
     *
     * @param  string $value
     * @return string
     */
    public function setAppSecret($value)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('app_secret', $value);
        }

        return $this->getAppSecret();
    }

    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['app_key'] = $this->getAppKey();

        return $result;
    }

    /**
     * Create UploadedFile from each picked Dropbox file and return result.
     *
     * @return array
     */
    public function onBatchAdd(array $files = [])
    {
        $result = [];

        DB::transact(function () use ($files, &$result) {
            foreach ($files as $file) {
                $attributes = [
                    'type' => DropboxUploadedFile::class,
                    'name' => $file['name'],
                    'size' => $file['bytes'],
                    'location' => date('Y-m') . '/' . make_string(40),
                ];

                $uploaded_file = UploadedFiles::create($attributes, false);

                if ($uploaded_file instanceof DropboxUploadedFile && isset($file['link'])) {
                    $uploaded_file->setUrl($file['link']);
                }

                $uploaded_file->save();

                $result[] = $uploaded_file;
            }
        }, 'Create dropbox uploaded files');

        return $result;
    }

    public function save()
    {
        try {
            DB::beginWork('Saving dropbox integration @ ' . __CLASS__);

            parent::save();
            ConfigOptions::setValue('initial_settings_timestamp', time());
            AngieApplication::cache()->remove('config_options');

            DB::commit('Dropbox integration saved @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to save dropbox integration @ ' . __CLASS__);
            throw $e;
        }
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting dropbox integration @ ' . __CLASS__);

            parent::delete($bulk);
            ConfigOptions::setValue('initial_settings_timestamp', time());
            AngieApplication::cache()->remove('config_options');

            DB::commit('Dropbox integration deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete dropbox integration @ ' . __CLASS__);
            throw $e;
        }
    }
}
