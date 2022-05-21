<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs;

use ActiveCollab\ActiveCollabJobs\Utils\HttpRequestDispatcher\HttpRequestDispatcherInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\WebhooksDispatcherInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\WebhooksHealthManagerInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface;
use ActiveCollab\ContainerAccess\ContainerAccessInterface\Implementation as ContainerAccessInterfaceImplementation;
use ActiveCollab\DatabaseConnection\ConnectionInterface;
use \ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\JobsQueue\Jobs\Job as BaseJob;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\ShepherdAccountConfig\Utils\ShepherdAccountConfigInterface;
use InvalidArgumentException;

/**
 * @property ConnectionInterface $connection
 * @property ConnectionInterface $shepherd_account_connection
 * @property JobsDispatcherInterface $dispatcher
 * @property LoggerInterface $log
 * @property ShepherdAccountConfigInterface $shepherd_account_config
 * @property HttpRequestDispatcherInterface $http_request_dispatcher
 * @property WebhooksDispatcherInterface $webhooks_dispatcher
 * @property WebhooksHealthManagerInterface $webhooks_health_manager
 */
abstract class Job extends BaseJob implements ContainerAccessInterface
{
    use ContainerAccessInterfaceImplementation;

    public function __construct(array $data = null)
    {
        if (empty($data['instance_id'])) {
            throw new InvalidArgumentException("'instance_id' property is required");
        }

        if (!is_int($data['instance_id'])) {
            if (is_string($data['instance_id']) && ctype_digit($data['instance_id'])) {
                $data['instance_id'] = (int) $data['instance_id'];
            } else {
                throw new InvalidArgumentException(
                    "Value '$data[instance_id]' is not a valid instance ID'"
                );
            }
        }

        parent::__construct($data);
    }

    protected function getInstanceId(): int
    {
        $instance_id = $this->getData('instance_id');

        if ($instance_id) {
            if (!is_int($instance_id) && ctype_digit($instance_id)) {
                $instance_id = (int) $instance_id;
            }

            if ($instance_id <= 0) {
                throw new InvalidArgumentException("Value '$instance_id' is not a valid instance ID");
            }

            return $instance_id;
        }

        throw new InvalidArgumentException('Instance ID not set');
    }
}
