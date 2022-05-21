<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\TimeRecordSourceResolver;

use ActiveCollab\Authentication\AuthenticationResult\AuthenticationResultInterface;
use ActiveCollab\Authentication\Session\SessionInterface;
use ApiSubscription;
use Psr\Log\LoggerInterface;

class TimeRecordSourceResolver implements TimeRecordSourceResolverInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getSource(
        AuthenticationResultInterface $authentication_result,
        ?string $source = null
    ): string
    {
        if ($authentication_result instanceof ApiSubscription) {
            return $authentication_result->getClientVendor() === 'POSTMAN' ? 'timer_app' : 'api_consumer';
        }

        if ($authentication_result instanceof SessionInterface &&
            $source &&
            in_array($source, self::TIME_RECORDS_VALUES)
        ) {
            return $source;
        }

        $this->logger->warning('Cannot resolve source.',
            [
                'method' => __METHOD__,
                'source' => $source,
            ]
        );

        return self::UNKNOWN;
    }
}
