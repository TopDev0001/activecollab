<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware;

use ActiveCollab\EmailReplyExtractor;
use ActiveCollab\Foundation\Mail\Incoming\Message\MessageInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Address\AddressInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\UnsupportedClientBounce;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Bounce\UnsupportedClientBounceInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\MiddlewareResultInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use Psr\Log\LoggerInterface;
use User;
use Users;

abstract class Middleware implements MiddlewareInterface
{
    protected DataObjectPoolInterface $data_object_pool;
    private LoggerInterface $logger;

    public function __construct(
        DataObjectPoolInterface $data_object_pool,
        LoggerInterface $logger
    )
    {
        $this->data_object_pool = $data_object_pool;
        $this->logger = $logger;
    }

    public function process(
        MessageInterface $message,
        AddressInterface $matched_recipient,
        string $source
    ): ?MiddlewareResultInterface
    {
        if ($message->getMailer() == EmailReplyExtractor::ANDROID_MAIL) {
            return new UnsupportedClientBounce(UnsupportedClientBounceInterface::ANDROID_MAIL);
        }

        return null;
    }

    protected function getSenderUser(MessageInterface $message): ?User
    {
        $user = Users::findByEmail($message->getSender());

        if ($user instanceof User && $user->isActive()) {
            return $user;
        }

        return null;
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info(
            $this->prepareLogMessage($message),
            $this->prepareLogContext($context)
        );
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning(
            $this->prepareLogMessage($message),
            $this->prepareLogContext($context)
        );
    }

    private function prepareLogMessage(string $message): string
    {
        return sprintf('Email Import: %s', $message);
    }

    private function prepareLogContext(array $context): array
    {
        return array_merge(
            $context,
            [
                'middleware' => get_class($this),
            ]
        );
    }
}
