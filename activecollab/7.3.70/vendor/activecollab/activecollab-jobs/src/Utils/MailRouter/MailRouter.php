<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\MailRouter;

use ActiveCollab\ActiveCollabJobs\Utils\EnvVarResolver\EnvVarResolverInterface;
use ActiveCollab\ActiveCollabJobs\Utils\JobDataResolver\JobDataResolverInterface;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class MailRouter implements MailRouterInterface
{
    private EnvVarResolverInterface $env_var_resolver;

    public function __construct(EnvVarResolverInterface $env_var_resolver)
    {
        $this->env_var_resolver = $env_var_resolver;
    }

    public function createFromJobData(JobDataResolverInterface $job_data_resolver): PHPMailer
    {
        if ($job_data_resolver->getArgumentValue('use_native_mailer')) {
            return $this->createNativeMailer();
        } else {
            return $this->createSmtpMailer(
                $job_data_resolver->getArgumentValue('route'),
                $job_data_resolver
            );
        }
    }

    public function createNativeMailer(): PHPMailer
    {
        return new PHPMailer(true);
    }

    public function createSmtpMailer(
        ?string $route,
        JobDataResolverInterface $job_data_resolver,
        bool $connect = true
    ): PHPMailer {
        $mailer = new PHPMailer(true);

        [
            $host,
            $port,
            $security,
            $username,
            $password,
            $verify_certificate
        ] = $this->getSmtpConnectionParams($route, $job_data_resolver);

        $mailer->isSMTP();

        $mailer->SMTPKeepAlive = true;
        $mailer->Host = $host;
        $mailer->Port = $port;

        if ($username && $password) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $username;
            $mailer->Password = $password;
        }

        if (!$verify_certificate) {
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        switch ($security) {
            case 'ssl':
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case 'tls':
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
            case 'auto':
                $mailer->SMTPAutoTLS = true;
                break;
        }

        if ($connect) {
            $mailer->smtpConnect($mailer->SMTPOptions);

            if (!$mailer->getSMTPInstance()->connected()) {
                throw new RuntimeException(
                    sprintf(
                        'SMTP is not connected to "%s" host. Route: %s.',
                        $host,
                        $route === null ? 'NULL' : $route
                    )
                );
            }
        }

        return $mailer;
    }

    private function getSmtpConnectionParams(
        ?string $route,
        JobDataResolverInterface $job_data_resolver
    ): array {
        if ($route === null) {
            return [
                $job_data_resolver->getArgumentValue('smtp_host'),
                $job_data_resolver->getArgumentValue('smtp_port'),
                $job_data_resolver->getArgumentValue('smtp_security'),
                $job_data_resolver->getArgumentValue('smtp_username'),
                $job_data_resolver->getArgumentValue('smtp_password'),
                (bool) $job_data_resolver->getArgumentValue('smtp_verify_certificate'),
            ];
        }

        $prefix = $route === 'default' ?
            'ACTIVECOLLAB_JOB_CONSUMER'
            : sprintf('ACTIVECOLLAB_%s', strtoupper($route));

        return [
            $this->env_var_resolver->getVariable(sprintf('%s_SMTP_ADDRESS', $prefix)),
            $this->env_var_resolver->getVariable(sprintf('%s_SMTP_PORT', $prefix)),
            $this->env_var_resolver->getVariable(sprintf('%s_SMTP_SECURITY', $prefix)),
            $this->env_var_resolver->getVariable(sprintf('%s_SMTP_USER', $prefix)),
            $this->env_var_resolver->getVariable(sprintf('%s_SMTP_PASS', $prefix)),
            (bool) $this->env_var_resolver->getVariable(sprintf('%s_SMTP_VERIFY_CERT', $prefix)),
        ];
    }
}
