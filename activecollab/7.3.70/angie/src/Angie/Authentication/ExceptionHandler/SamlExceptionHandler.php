<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\ExceptionHandler;

use ActiveCollab\Authentication\Authorizer\ExceptionAware\ExceptionHandler\ExceptionHandlerInterface;
use Angie\Http\Response\MovedResource\MovedResource;
use AngieApplication;

/**
 * @package Angie\Authentication\ExceptionHandler
 */
class SamlExceptionHandler implements ExceptionHandlerInterface
{
    public function handleException(array $credentials, $error_or_exception)
    {
        return new MovedResource(SHEPHERD_URL . '/profile?forbidden_access_to=' . AngieApplication::getAccountId(), false);
    }
}
