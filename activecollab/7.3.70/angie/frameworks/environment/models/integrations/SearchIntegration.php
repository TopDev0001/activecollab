<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\TestConnection;

class SearchIntegration extends Integration implements SearchIntegrationInterface
{
    const JOBS_QUEUE_CHANNEL = 'search';
    const ACTIVE_COLLAB_DEFAULT_SHARDS = 2;
    const ACTIVE_COLLAB_DEFAULT_REPLICAS = 2;
    const ELASTIC_SEARCH_DEFAULT_SHARDS = 5;
    const ELASTIC_SEARCH_DEFAULT_REPLICAS = 1;

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Search';
    }

    public function getShortName(): string
    {
        return 'search';
    }

    public function getDescription()
    {
        return lang('Use ElasticSearch to index and search your data');
    }

    public function isAvailableForOnDemand()
    {
        return false; // Use pre-configured search and don't let settings be changed via API
    }

    public function getMinVersion()
    {
        return '6.0';
    }

    // ---------------------------------------------------
    //  ElasticSearch specific
    // ---------------------------------------------------

    public function configure(array $data)
    {
        Integrations::update($this, $data);

        $force = (bool) array_key_exists('force', $data) ? $data['force'] : true;

        AngieApplication::search()->createIndex($force);

        $this->setIndexCreatedFlag(true);
        $this->save();

        return $this;
    }

    public function testConnection($hosts, $execute_now = true)
    {
        $job = new TestConnection(
            [
                'instance_id' => AngieApplication::getAccountId(),
                'hosts' => $hosts,
                'min_version' => $this->getMinVersion(),
                'successful_connection_message' => lang('Successfully connected to cluster :cluster_name, running ElasticSearch :version_found.'),
                'min_version_not_met' => lang('Connection was successful, but we found ElasticSearch v:version_found (v:version_required is required). Please upgrade your ElasticSearch and try again.'),
                'unexpected_response' => lang('Unexpected cluster response. Array of details expected, got :what_we_got.'),
            ]
        );

        if ($execute_now) {
            return AngieApplication::jobs()->execute($job, false);
        } else {
            // Used just for job creation testing. Connection testing in the background is kind of pointless
            return AngieApplication::jobs()->dispatch($job, self::JOBS_QUEUE_CHANNEL);
        }
    }

    public function disconnect()
    {
        try {
            AngieApplication::search()->deleteIndex();
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to delete search index on disconnect',
                [
                    'exception' => $e,
                ]
            );
        }

        DB::execute('UPDATE integrations SET raw_additional_properties = ? WHERE type = ?', null, self::class);

        AngieApplication::cache()->removeByObject($this);

        return Integrations::findFirstByType(self::class);
    }

    public function getHosts()
    {
        return $this->getAdditionalProperty('hosts');
    }

    /**
     * @param  int    $value
     * @return string
     */
    public function setHosts($value)
    {
        return $this->setAdditionalProperty('hosts', $value);
    }

    public function getShards()
    {
        if (AngieApplication::isOnDemand()) {
            return self::ACTIVE_COLLAB_DEFAULT_SHARDS;
        }

        return $this->getAdditionalProperty('shards')
            ? $this->getAdditionalProperty('shards')
            : self::ELASTIC_SEARCH_DEFAULT_SHARDS;
    }

    /**
     * @param  int    $value
     * @return string
     */
    public function setShards($value)
    {
        if (AngieApplication::isOnDemand()) {
            return self::ACTIVE_COLLAB_DEFAULT_SHARDS;
        }

        return $this->setAdditionalProperty(
            'shards',
            $value ? (int) $value : null
        );
    }

    public function getReplicas()
    {
        if (AngieApplication::isOnDemand()) {
            return self::ACTIVE_COLLAB_DEFAULT_REPLICAS;
        }

        return $this->getAdditionalProperty('replicas')
            ? $this->getAdditionalProperty('replicas')
            : self::ELASTIC_SEARCH_DEFAULT_REPLICAS;
    }

    /**
     * @param  int    $value
     * @return string
     */
    public function setReplicas($value)
    {
        if (AngieApplication::isOnDemand()) {
            return self::ACTIVE_COLLAB_DEFAULT_REPLICAS;
        }

        return $this->setAdditionalProperty(
            'replicas',
            $value ? (int) $value : null
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'hosts' => $this->getHosts(),
            'shards' => $this->getShards(),
            'replicas' => $this->getReplicas(),
            'is_index_created' => $this->isIndexCreated(),
            'is_index_rebuilded' => $this->isIndexRebuilded(),
        ]);
    }

    /**
     * Returns true if search is configured properly.
     *
     * @return bool
     */
    public function isOk(array & $error_messages = null)
    {
        if (!($this->isIndexCreated() && $this->isIndexRebuilded())) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('Search server is not configured');
            }
        }

        return empty($error_messages);
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Check is index created.
     *
     * @return bool
     */
    public function isIndexCreated()
    {
        return $this->getAdditionalProperty('is_index_created') === true;
    }

    /**
     * Set value for 'is_index_created' flag.
     *
     * @param bool $value
     */
    public function setIndexCreatedFlag($value)
    {
        $this->setAdditionalProperty('is_index_created', $value);
    }

    /**
     * Check is index rebulided.
     *
     * @return bool
     */
    public function isIndexRebuilded()
    {
        return $this->getAdditionalProperty('is_index_rebuilded') === true;
    }

    /**
     * Set value for 'is_index_rebuilded' flag.
     *
     * @param bool $value
     */
    public function setIndexRebuildedFlag($value)
    {
        $this->setAdditionalProperty('is_index_rebuilded', $value);
    }
}
