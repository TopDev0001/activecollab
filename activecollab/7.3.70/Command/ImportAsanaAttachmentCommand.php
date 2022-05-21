<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use Asana\Client;
use AsanaImporterIntegration;
use DataObject;
use DataObjectPool;
use Exception;
use IAttachments;
use IAttachmentsImplementation;
use Integrations;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use User;
use Users;

class ImportAsanaAttachmentCommand extends Command
{
    private $client;

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Import Asana Attachment')
            ->addOption('context-type', null, InputOption::VALUE_REQUIRED, 'Type of context to which file will be attached')
            ->addOption('context-id', null, InputOption::VALUE_REQUIRED, 'ID of context to which file will be attached')
            ->addOption('attachment-id', null, InputOption::VALUE_REQUIRED, 'ID of the remote file which will be downloaded and attached to the context')
            ->addOption('mime-type', null, InputOption::VALUE_REQUIRED, 'MIME type of the file (e.g. application/octet-stream')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'ID of the user who has uploaded attachment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $token = (Integrations::findFirstByType(AsanaImporterIntegration::class))->getAccessToken();

            $this->client = Client::accessToken($token);
            $this->client->options['max_retries'] = 10;
        } catch (Exception $e) {
            AngieApplication::log()->warning('Attachment skipped during Asana import', [
                'comment_body' => 'Autorization failed.',
            ]);
        }

        try {
            $context = $this->resolveContext($input);
            $attachment_id = $input->getOption('attachment-id');

            $attach = $this->getAttachmentFromAsana($attachment_id);

            $file = $this->downloadAttachment($attach->download_url, $attach->name);

            $user = Users::findById((int) $input->getOption('user-id'));

            if (!$user instanceof User){
                $user = null;
            }

            if ($file !== null) {
                $context->attachFile($file, $attach->name, $input->getOption('mime-type'), $user);
            } else {
                AngieApplication::log()->warning('c', [
                    'comment_body' => 'File does not exist.',
                ]);

                throw new RuntimeException('Attachment skipped during Asana import. File does not exist.');
            }

            return $this->success('Done', $input, $output);
        } catch (Throwable $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    /**
     * @return DataObject|IAttachmentsImplementation
     */
    private function resolveContext(InputInterface $input)
    {
        $context_type = trim($input->getOption('context-type'));
        $context_id = (int) $input->getOption('context-id');

        if (empty($context_type)) {
            throw new InvalidArgumentException("'context-type' option is required and can not be empty");
        }

        $context = DataObjectPool::get($context_type, $context_id);

        if (!$context instanceof $context_type) {
            throw new RuntimeException('Failed to find context');
        } elseif (!$context instanceof IAttachments) {
            throw new LogicException('Can not attach a file to this context');
        }

        return $context;
    }

    /**
     * @param $url
     * @return string|null
     */
    private function downloadAttachment($url, $origin_file_name)
    {
        $ac_tmp_attachments = WORK_PATH . '/' . AngieApplication::getAccountId() . '-asana_attachments';
        recursive_mkdir($ac_tmp_attachments);

        $ext = '.png';

        do {
            $filename = $ac_tmp_attachments . '/' . $origin_file_name . make_string() . $ext;
        } while (is_file($filename));

        set_time_limit(0);
        $file = fopen($filename, 'w+');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        unset($ch);

        fclose($file);

        if (!is_file($filename)){
            AngieApplication::log()->warning('Attachment skipped during Asana import', [
                'comment_body' => 'Attachment skipped because it`s not file.',
                'response_code' => $code,
                'file_url' => $url,
            ]);

            return null;
        } elseif (($code < 200) || ($code >= 300)) {
            AngieApplication::log()->warning('Attachment skipped during Asana import', [
                'comment_body' => 'Attachment skipped because HTTP response code is: ' . $code,
                'file_url' => $url,
            ]);

            return null;
        }

        return $filename;
    }

    protected function getAttachmentFromAsana($attachment_id)
    {
        $path = sprintf('/attachments/%s', $attachment_id);
        $attach = $this->client->request('GET', $path, []);

        return $attach;
    }
}
