<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Imap;

use ActiveCollab\JobsQueue\Helpers\Port;
use Fetch\Server;
use InvalidArgumentException;
use RuntimeException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Imap
 */
class TestConnection extends Job
{
    use Port;

    /**
     * Construct a new Job instance.
     *
     * @param  array|null               $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = null)
    {
        foreach (['host', 'username', 'password', 'mailbox'] as $check) {
            if (empty($data[$check])) {
                throw new InvalidArgumentException("'$check' property is required");
            }
        }

        $this->validatePort($data, 143);

        if (empty($data['security'])) {
            $data['security'] = '';
        }

        parent::__construct($data);
    }

    /**
     * Test connection and validate TRUE if all is good, or error message in case on an error.
     *
     * @return bool|string
     */
    public function execute()
    {
        $server = new Server($this->getData()['host'], $this->getData()['port']);

        if ($this->getData()['verify_certificate'] == false) {
            $server->setFlag('novalidate-cert', true);
        }

        switch ($this->getData()['security']) {
            case 'ssl':
                $server->setFlag('ssl', true);
                break;
            case 'tls':
                $server->setFlag('tls', true);
                break;
            case 'none':
                $server->setFlag('notls', true);
                break;
        }

        $server->setAuthentication($this->getData()['username'], $this->getData()['password']);

        try {
            $server->getImapStream(); // imap_open

            if ($server->hasMailBox($this->getData()['mailbox'])) {
                return $response = [
                    'debug' => '',
                    'message' => '',
                    'isOk' => true,
                ];
            } else {
                $message = "Error: IMAP connection has been established, but mailbox '" . $this->getData()['mailbox'] . "' not found";

                return $response = [
                    'debug' => $message,
                    'message' => $message,
                    'isOk' => false,
                ];
            }
        } catch (RuntimeException $e) {
            $message = 'Error: ' . $e->getMessage();

            return $response = [
                'debug' => $message,
                'message' => $message,
                'isOk' => false,
            ];
        }
    }
}
