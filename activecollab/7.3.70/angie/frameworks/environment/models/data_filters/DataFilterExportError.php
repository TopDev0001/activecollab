<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Exception that is thrown when export fails.
 *
 * @package angie.frameworks.reports
 * @subpackage models
 */
class DataFilterExportError extends Error
{
    /**
     * Error code.
     *
     * @var int
     */
    protected $export_code;

    /**
     * Construct new error instance.
     *
     * @param int    $code
     * @param string $message
     */
    public function __construct($code, $message = null)
    {
        if ($message === null) {
            switch ($code) {
                case DataFilter::EXPORT_ERROR_ALREADY_STARTED:
                    $message = 'Export already initiated';
                    break;
                case DataFilter::EXPORT_ERROR_CANT_OPEN_HANDLE:
                    $message = 'Cannot open temp file handle for export';
                    break;
                case DataFilter::EXPORT_ERROR_HANDLE_NOT_OPEN:
                    $message = 'Export temp file handle not open';
                    break;
                default:
                    $message = 'Unknown export error';
            }
        }

        $this->export_code = $code;

        parent::__construct($message, [
            'code' => $code,
        ]);
    }
}
