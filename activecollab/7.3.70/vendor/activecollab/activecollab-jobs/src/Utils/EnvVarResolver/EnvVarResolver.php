<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\EnvVarResolver;

use Psr\Log\LoggerInterface;

class EnvVarResolver implements EnvVarResolverInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getVariable(string $variable_name, ?string $default = null): ?string
    {
        $value = getenv($variable_name);

        if ($value === false) {
            $this->logger->error(
                'Environment variable {env_var} not found.',
                [
                    'env_var' => $variable_name,
                ]
            );

            return $default;
        }

        return $value;
    }
}
