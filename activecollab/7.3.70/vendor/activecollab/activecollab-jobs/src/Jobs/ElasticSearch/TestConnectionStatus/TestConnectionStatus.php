<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\TestConnectionStatus;

use Exception;

final class TestConnectionStatus implements TestConnectionStatusInterface
{
    private $min_version_required;

    /**
     * @var string
     */
    private $successful_connection_message;

    /**
     * @var string
     */
    private $min_version_not_met;

    /**
     * @var string
     */
    private $unexpected_response;

    private $success;

    private $cluster_info;

    private $failure_reason;

    /**
     * TestConnectionStatus constructor.
     *
     * @param array|Exception $response
     * @param string          $min_version_required
     * @param string          $successful_connection_message
     * @param string          $min_version_not_met
     * @param string          $unexpected_response
     */
    public function __construct(
        $response,
        $min_version_required,
        $successful_connection_message = 'Successfully connected to cluster :cluster_name, running ElasticSearch :version_found.',
        $min_version_not_met = 'Connection was successful, but we found ElasticSearch v:version_found (v:version_required is required). Please upgrade your ElasticSearch and try again.',
        $unexpected_response = 'Unexpected cluster response. Array of details expected, got :what_we_got.'
    ) {
        $this->min_version_required = $min_version_required;
        $this->successful_connection_message = $successful_connection_message;
        $this->min_version_not_met = $min_version_not_met;
        $this->unexpected_response = $unexpected_response;

        if ($response instanceof Exception) {
            $this->processException($response);
        } else {
            $this->processClusterResponse($response);
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @return string
     */
    public function getClusterInfo()
    {
        return $this->cluster_info;
    }

    /**
     * @return string
     */
    public function getFailureReason()
    {
        return $this->failure_reason;
    }

    // ---------------------------------------------------
    //  Utility
    // ---------------------------------------------------

    private function processClusterResponse($cluster_response)
    {
        if ($this->isValidClusterResponse($cluster_response)) {
            $version = $cluster_response['version']['number'];

            if ($this->isMinVersionRequirementMet($version)) {
                $this->setAsSuccess(
                    $this->successful_connection_message,
                    [
                        'cluster_name' => $cluster_response['cluster_name'],
                        'version_found' => $version,
                    ]
                );
            } else {
                $this->setAsFailed(
                    $this->min_version_not_met,
                    [
                        'version_found' => $version,
                        'version_required' => $this->min_version_required,
                    ]
                );
            }
        } else {
            $this->setAsFailed(
                $this->unexpected_response,
                [
                    'what_we_got' => is_object($cluster_response) ? get_class($cluster_response) : gettype($cluster_response),
                ]
            );
        }
    }

    private function isValidClusterResponse($cluster_response)
    {
        return is_array($cluster_response)
            && !empty($cluster_response['cluster_name'])
            && !empty($cluster_response['version']['number']);
    }

    private function isMinVersionRequirementMet($version)
    {
        if ($this->min_version_required && version_compare($this->min_version_required, $version, '>')) {
            return false;
        }

        return true;
    }

    private function processException(Exception $exception)
    {
        $this->setAsFailed($exception->getMessage() . ' (' . get_class($exception) . ').');
    }

    private function setAsSuccess($message, array $message_arguments = null)
    {
        $this->success = true;
        $this->cluster_info = $message;

        foreach ($message_arguments as $k => $v) {
            $this->cluster_info = str_replace(":{$k}", $v, $this->cluster_info);
        }
    }

    private function setAsFailed($message, array $message_arguments = null)
    {
        $this->success = false;
        $this->failure_reason = $message;

        foreach ($message_arguments as $k => $v) {
            $this->failure_reason = str_replace(":{$k}", $v, $this->failure_reason);
        }
    }
}
