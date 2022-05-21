<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class which extends Fetch library to enable connecting to local .eml file.
 *
 * @package angie.frameworks.email
 * @subpackage models
 */
class FileMailbox extends \Fetch\Server
{
    /**
     * This function takes in all of the connection data (server, port, service, flags, mailbox) and creates the string
     * that's passed to the imap_open function.
     *
     * Overrides parent \Fetch\Server function
     *
     * @return string
     */
    protected function getServerSpecification()
    {
        return $this->serverPath;
    }
}
