<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Smtp;

use ActiveCollab\JobsQueue\Helpers\Port;
use ActiveCollab\JobsQueue\Jobs\Job;
use Exception;
use InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Smtp
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
        $data_to_check = ['host', 'username', 'password'];

        if ($data['security'] == 'auto') {
            $data_to_check = ['host'];
        }

        foreach ($data_to_check as $check) {
            if (empty($data[$check])) {
                throw new InvalidArgumentException("'$check' property is required");
            }
        }

        $this->validatePort($data, 25);

        if (empty($data['security'])) {
            $data['security'] = '';
        }

        parent::__construct($data);
    }

    /**
     * @return bool|string
     */
    public function execute()
    {
        $server = new PHPMailer(true);

        if ($this->getData()['verify_certificate'] == false) {
            $server->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $server->isSMTP();
        $server->Host = $this->getData()['host'];
        $server->Port = $this->getData()['port'];

        $server->SMTPAuth = true;

        switch ($this->getData()['security']) {
            case 'ssl':
                $server->SMTPSecure = 'ssl';
                break;
            case 'tls':
                $server->SMTPSecure = 'tls';
                break;
            case 'auto':
                $server->SMTPAuth = false;
                break;
        }

        $server->Username = $this->getData()['username'];
        $server->Password = $this->getData()['password'];
        $server->SMTPDebug = 4;

        $debug_output = '';
        $server->Debugoutput = function ($str) use (&$debug_output) {
            $debug_output .= "$str\n";
        };

        try {
            $server->setFrom($this->getData()['sender_email']);     // Your email
            $server->addAddress($this->getData()['logged_user_email'], $this->getData()['logged_user_email']); // On Which email to send password
            $server->Subject = 'Active Collab SMTP Test';
            $server->msgHTML("You did it!<br><br>You've successfully configured your Active Collab's SMTP settings.<br>Now, Active Collab can start sending notifications, morning recaps, and other emails to you and your team.<br><br>Happy collaborating!");

            if (!$server->send()) {
                $response = [
                    'debug' => $debug_output,
                    'message' => '',
                    'isOk' => false,
                ];
            } else {
                $response = [
                    'debug' => '',
                    'message' => '',
                    'isOk' => true,
                ];
            }

            return $response;
        } catch (Exception $e) {
            $response = [
                'debug' => $debug_output,
                'message' => htmlspecialchars($e->getMessage()),
                'isOk' => false,
            ];

            return $response;
        }
    }
}
