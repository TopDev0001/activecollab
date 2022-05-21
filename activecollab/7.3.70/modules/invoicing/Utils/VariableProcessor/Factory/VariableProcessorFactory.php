<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory;

use ActiveCollab\Authentication\AuthenticationInterface;
use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessor;
use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use ActiveCollab\Foundation\Text\VariableProcessor\VariableProvider\DateVariableProvider;
use Angie\Utils\SystemDateResolver\SystemDateResolverInterface;
use Angie\Utils\UserDateResolver\UserDateResolverInterface;
use DateValue;
use User;

class VariableProcessorFactory implements VariableProcessorFactoryInterface
{
    private SystemDateResolverInterface $system_date_resolver;
    private UserDateResolverInterface $user_date_resolver;
    private AuthenticationInterface $authentication;

    public function __construct(
        SystemDateResolverInterface $system_date_resolver,
        UserDateResolverInterface $user_date_resolver,
        AuthenticationInterface $authentication
    )
    {
        $this->system_date_resolver = $system_date_resolver;
        $this->user_date_resolver = $user_date_resolver;
        $this->authentication = $authentication;
    }

    public function createForTaskList(DateValue $reference_date = null): VariableProcessorInterface
    {
        return new VariableProcessor(
            new DateVariableProvider($this->getReferenceDate($reference_date))
        );
    }

    public function createForTask(DateValue $reference_date = null): VariableProcessorInterface
    {
        return new VariableProcessor(
            new DateVariableProvider($this->getReferenceDate($reference_date))
        );
    }

    public function createForSubtask(DateValue $reference_date = null): VariableProcessorInterface
    {
        return new VariableProcessor(
            new DateVariableProvider($this->getReferenceDate($reference_date))
        );
    }

    public function createForDiscussion(DateValue $reference_date = null): VariableProcessorInterface
    {
        return new VariableProcessor(
            new DateVariableProvider($this->getReferenceDate($reference_date))
        );
    }

    public function createForNote(DateValue $reference_date = null): VariableProcessorInterface
    {
        return new VariableProcessor(
            new DateVariableProvider($this->getReferenceDate($reference_date))
        );
    }

    public function createForInvoice(DateValue $reference_date = null): VariableProcessorInterface
    {
        return new VariableProcessor(
            new DateVariableProvider($this->getReferenceDate($reference_date))
        );
    }

    private function getReferenceDate(DateValue $reference_date = null): DateValue
    {
        if ($reference_date) {
            return $reference_date;
        }

        $authenticated_user = $this->authentication->getAuthenticatedUser();

        if ($authenticated_user instanceof User) {
            return $this->user_date_resolver->getUserDate($authenticated_user);
        }

        return $this->system_date_resolver->getSystemDate();
    }
}
