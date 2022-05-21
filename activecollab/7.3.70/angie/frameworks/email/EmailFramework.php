<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Modules\AngieFramework;

/**
 * Email framework definition.
 *
 * @package angie.frameworks.email
 */
class EmailFramework extends AngieFramework
{
    const NAME = 'email';

    const MAILING_SILENT = 'silent';
    const MAILING_NATIVE = 'native';
    const MAILING_QUEUED = 'queued';

    protected string $name = 'email';

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                EmailNotificationChannel::class => __DIR__ . '/models/EmailNotificationChannel.php',

                EmailIntegration::class => __DIR__ . '/models/EmailIntegration.php',

                IncomingMail::class => __DIR__ . '/models/IncomingMail.php',
                FileMailbox::class => __DIR__ . '/models/FileMailbox.php',
                IIncomingMail::class => __DIR__ . '/models/IIncomingMail.php',

                JsonEmailProcessor::class => __DIR__ . '/models/JsonEmailProcessor.php',
                EmailImporterInterface::class => __DIR__ . '/models/EmailImporterInterface.php',
                OnDemandEmailImporter::class => __DIR__ . '/models/OnDemandEmailImporter.php',
                EmailProcessorInterface::class => __DIR__ . '/models/EmailProcessorInterface.php',
                EmailMessageInterface::class => __DIR__ . '/models/EmailMessageInterface.php',
                EmailMessage::class => __DIR__ . '/models/EmailMessage.php',

                FwNotifyEmailSenderNotification::class => __DIR__ . '/notifications/FwNotifyEmailSenderNotification.class.php',
                IncomingMailMessage::class => __DIR__ . '/models/IncomingMailMessage.php',
                FwBounceEmailNotification::class => __DIR__ . '/notifications/FwBounceEmailNotification.class.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_system_status');
    }
}
