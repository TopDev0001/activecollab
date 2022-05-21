<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Incoming mail interface.
 *
 * @package angie.frameworks.email
 * @subpackage models
 */
interface IIncomingMail
{
    /**
     * Process incoming mail and return resulting object (or null if message can't be handled).
     *
     * @param  IUser[]         $to
     * @param  string          $subject
     * @param  string          $text
     * @return DataObject|null
     */
    public function processIncomingMail(IUser $from, array $to, $subject, $text, array $attachments = null);
}
