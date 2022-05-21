<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface SearchIntegrationInterface
{
    /**
     * Return minimal ElasticSearch version that is required by the system.
     *
     * @return string
     */
    public function getMinVersion();

    /**
     * @return string|null
     */
    public function getHosts();

    /**
     * @return int|null
     */
    public function getShards();

    /**
     * @return int|null
     */
    public function getReplicas();

    /**
     * Test connection to ElasticSearch server.
     *
     * @param  array     $hosts
     * @param  bool|true $execute_now
     * @return mixed
     */
    public function testConnection($hosts, $execute_now = true);

    /**
     * Disconnect from ElasticSearch server.
     *
     * @return mixed
     */
    public function disconnect();

    /**
     * Configure ElasticSearch index.
     *
     * @return SearchIntegration
     */
    public function configure(array $data);
}
