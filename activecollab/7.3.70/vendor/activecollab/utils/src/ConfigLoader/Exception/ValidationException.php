<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ConfigLoader\Exception;

use Exception;
use RuntimeException;

class ValidationException extends RuntimeException
{
    private $missing_options = [];

    private $missing_option_values = [];

    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Found config options do not match configured requirements.';
        }

        parent::__construct($message, $code, $previous);
    }

    public function &missing($option_name)
    {
        if (!in_array($option_name, $this->missing_options)) {
            $this->missing_options[] = $option_name;
            $this->rebuildExceptionMessage();
        }

        return $this;
    }

    public function &missingValue($option_name)
    {
        if (!in_array($option_name, $this->missing_option_values)) {
            $this->missing_option_values[] = $option_name;
            $this->rebuildExceptionMessage();
        }

        return $this;
    }

    public function hasErrors()
    {
        return !empty($this->missing_options) || !empty($this->missing_option_values);
    }

    private function rebuildExceptionMessage()
    {
        $this->message = 'Found config options do not match configured requirements.';

        if (!empty($this->missing_options)) {
            $this->message .= ' Options not present: ' . implode(', ', $this->missing_options) . '.';
        }

        if (!empty($this->missing_option_values)) {
            $this->message .= ' Options not present, or empty: ' . implode(', ', $this->missing_option_values) . '.';
        }
    }
}
