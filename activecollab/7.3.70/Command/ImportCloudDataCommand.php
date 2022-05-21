<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command;

use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use Angie\Command\Command;
use AngieApplication;
use DB;
use DirectoryCreateError;
use Exception;
use InvalidArgumentException;
use Owner;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportCloudDataCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Import Cloud archive')
            ->addArgument(
                'export_file_path',
                InputArgument::REQUIRED,
                'Path to data export file.'
            )
            ->addArgument(
                'application_unique_key',
                InputArgument::OPTIONAL,
                'Change application unique key to this value in config/config.php file.'
            )
            ->addArgument(
                'owner_email_address',
                InputArgument::OPTIONAL,
                'Enter email address of the owner who is doing the import.'
            )
            ->addOption(
                'mysql_bin',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to mysql binary.',
                'mysql'
            )
            ->addOption(
                'rsync_bin',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to rsync binary.',
                'rsync'
            )
            ->addOption(
                'unzip_bin',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to unzip binary.',
                'unzip'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                "Force import and don't ask for import consent."
            )
            ->addOption(
                'proxy',
                '',
                InputOption::VALUE_NONE,
                'Address of proxy server that should be used when downloading export file.'
            )
            ->addOption(
                'skip-ssl-verification',
                '',
                InputOption::VALUE_NONE,
                'Skip SSL peer verification when downloading export file.'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'New password for owner specified by <comment>owner_email_address</comment> argument.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if (DIRECTORY_SEPARATOR == '\\') {
                return $this->abort(
                    '<error>Error</error>: This command is not available on Windows systems.',
                    255,
                    $input,
                    $output
                );
            }

            if (!$input->getOption('force') && !$this->hasConsent($input, $output)) {
                return $this->success('Operation aborted.', $input, $output);
            }

            $output->writeln('<info>OK</info>: Increasing memory limit...');
            ini_set('memory_limit', '-1');

            $owner_email_address = $this->getOwnerEmailAddress($input, $output);
            $new_owner_password = $this->getNewOwnerPassword($owner_email_address, $input, $output);
            $mysql_bin = $this->getMySqlBin($input, $output);
            $rsync_bin = $this->getRsyncBin($input, $output);
            $unzip_bin = $this->getUnzipBin($input, $output);
            $export_file_path = $this->getExportFilePath($input, $output);
            $import_work_path = $this->getImportWorkPath(AngieApplication::getAccountId(), $export_file_path, $output);

            [
                'sql_file_path' => $sql_file_path,
                'upload_dir_path' => $upload_dir_path,
            ] = $this->extractArchive($unzip_bin, $export_file_path, $import_work_path, $output);

            if (DB::tableExists('users')) {
                $number_of_users = DB::executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `users`');
            } else {
                $number_of_users = 0;
            }

            $this->importSql($mysql_bin, $sql_file_path, $output);
            $this->rsyncUploadedFiles($rsync_bin, $upload_dir_path, $output);
            $this->cleanUp($import_work_path, $output);
            $this->updateApplicationUniqueKey(
                (string) $input->getArgument('application_unique_key'),
                $output
            );

            $this->changeOwnerPassword($owner_email_address, $new_owner_password, $output);
            $this->communicateImport($number_of_users, $output);

            return $this->success('Done!', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    private function hasConsent(InputInterface $input, OutputInterface $output): bool
    {
        $output->writeln('<info>ActiveCollab Cloud Data Imported Utility</info>');
        $output->writeln("This command will import data from ActiveCollab Cloud export, and <warn>OVERWRITE ALL THE DATA</warn> that you have in this ActiveCollab. <warn>This operation can't be undone!</warn>");

        $consent = (string) $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new Question('Proceed (y/n)? ')
            );

        return strtolower($consent) === 'yes'
            || strtolower($consent) === 'y';
    }

    private function getOwnerEmailAddress(InputInterface $input, OutputInterface $output): string
    {
        $owner_email_address = (string) $input->getArgument('owner_email_address');

        if ($owner_email_address && !filter_var($owner_email_address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf("Valid email address is expected, '%s' was given.", $owner_email_address));
        }

        $output->writeln(
            sprintf(
                '<info>OK</info>: Owner email address is <comment>%s</comment>.',
                $owner_email_address
            )
        );

        return $owner_email_address;
    }

    private function getNewOwnerPassword(
        string $owner_email_address,
        InputInterface $input,
        OutputInterface $output
    ): string
    {
        if (empty($owner_email_address)) {
            return '';
        }

        $password = $input->getOption('password');

        if (empty($password)) {
            $password = $this
                ->getHelper('question')
                ->ask(
                    $input,
                    $output,
                    (new Question(
                        sprintf('Enter password for <comment>%s</comment>: ', $owner_email_address))
                    )
                        ->setHidden(true)
                        ->setHiddenFallback(false)
                );

            if (empty($password)) {
                throw new RuntimeException('Password is required');
            }
        }

        return $password;
    }

    private function getMySqlBin(InputInterface $input, OutputInterface $output): string
    {
        $mysql_bin = $input->getOption('mysql_bin');

        $command_output = $this->executeCommand("{$mysql_bin} -V");

        $output->writeln(
            sprintf(
                '<info>OK</info>: MySQL binnary is <comment>%s</comment>. Version info: <comment>%s</comment>.',
                $mysql_bin,
                substr($command_output[0], 0, strpos($command_output[0], ','))
            )
        );

        return $mysql_bin;
    }

    private function getRsyncBin(InputInterface $input, OutputInterface $output): string
    {
        $rsync_bin = $input->getOption('rsync_bin');

        $command_output = $this->executeCommand("{$rsync_bin} --help");

        $output->writeln(
            sprintf(
                '<info>OK</info>: rsync binary is <comment>%s</comment>. Version info: <comment>%s</comment>.',
                $rsync_bin,
                $command_output[0]
            )
        );

        return $rsync_bin;
    }

    private function getUnzipBin(InputInterface $input, OutputInterface $output): string
    {
        $unzip_bin = $input->getOption('unzip_bin');

        $command_output = $this->executeCommand("{$unzip_bin} -v");

        $output->writeln(
            sprintf(
                '<info>OK</info>: Unzip binary is <comment>%s</comment>. Version info: <comment>%s</comment>.',
                $unzip_bin,
                implode(' ', array_slice(explode(' ', $command_output[0]), 0, 2))
            )
        );

        return $unzip_bin;
    }

    private function getExportFilePath(InputInterface $input, OutputInterface $output): string
    {
        $export_file_path = $input->getArgument('export_file_path');

        if (filter_var($export_file_path, FILTER_VALIDATE_URL)) {
            return $this->downloadExportFile($export_file_path, $input, $output);
        } else {
            return $this->validateExportFilePath($export_file_path, $output);
        }
    }

    private function downloadExportFile(
        string $export_file_url,
        InputInterface $input,
        OutputInterface $output
    ): string
    {
        $work_path = implode(
            '/',
            [
                WORK_PATH,
                date('Y-m'),
            ]
        );

        if (!is_dir($work_path)) {
            $old_umask = umask(0000);
            $dir_created = mkdir($work_path, 0777, true);
            umask($old_umask);

            if (empty($dir_created)) {
                throw new DirectoryCreateError($work_path);
            }
        }

        $destination_file = "{$work_path}/cloud-data-export.zip";

        $output->writeln(
            sprintf('<info>OK</info>: Downloading export archive to <comment>%s</comment>...', $destination_file)
        );
        $output->writeln('');

        $progress = new ProgressBar($output, 100);
        $progress->start();

        $destination_file = $this->downloadFromUrl(
            $export_file_url,
            $destination_file,
            function ($percent) use (&$progress) {
                $progress->setProgress($percent);
            },
            $input
        );

        $progress->finish();

        $output->writeln('');
        $output->writeln('');

        return $destination_file;
    }

    private function validateExportFilePath(string $export_file_path, OutputInterface $output): string
    {
        if (!is_file($export_file_path)) {
            throw new RuntimeException("Export file not found at '{$export_file_path}'.");
        }

        if (!str_ends_with($export_file_path, '.zip')) {
            throw new RuntimeException("Export file found at '{$export_file_path}', but it is not .zip file.");
        }

        $output->writeln(
            sprintf('<info>OK</info>: Export file found at <comment>%s</comment>.', $export_file_path)
        );

        return $export_file_path;
    }

    private function getImportWorkPath(int $account_id, string $export_file_path, OutputInterface $output): string
    {
        $work_path = implode(
            '/',
            [
                WORK_PATH,
                date('Y-m'),
                $account_id . '-' . sha1($export_file_path) . '-import',
            ]
        );

        if (!is_dir($work_path)) {
            $old_umask = umask(0000);
            $dir_created = mkdir($work_path, 0777, true);
            umask($old_umask);

            if (empty($dir_created)) {
                throw new DirectoryCreateError($work_path);
            }
        }

        $output->writeln(sprintf('<info>OK</info>: Temp work path is <comment>%s</comment>.', $work_path));

        return $work_path;
    }

    private function extractArchive(
        string $unzip_bin,
        string $export_file_path,
        string $import_work_path,
        OutputInterface $output
    ): array
    {
        $output->writeln('<info>OK</info>: Extracting archive...');

        $command_output = $this->executeCommand(
            sprintf(
                $unzip_bin . ' -o %s -d %s',
                escapeshellarg($export_file_path),
                escapeshellarg($import_work_path)
            )
        );

        if ($output->isVerbose()) {
            foreach ($command_output as $command_output_line) {
                $output->writeln('    ' . $command_output_line);
            }
        }

        $sql_file_path = "{$import_work_path}/export/data.sql";
        $upload_dir_path = "{$import_work_path}/export/upload/";
        $files_index_path = "{$upload_dir_path}/files_index.json";

        foreach ([$sql_file_path, $files_index_path] as $expected_file_path) {
            if (!is_file($expected_file_path)) {
                throw new RuntimeException("File '{$expected_file_path}' not found after archive extraction.");
            }
        }

        if (!is_dir($upload_dir_path)) {
            throw new RuntimeException("Directory '{$upload_dir_path}' not found after archive extraction.");
        }

        return [
            'sql_file_path' => $sql_file_path,
            'upload_dir_path' => $upload_dir_path,
            'files_index_path' => $files_index_path,
        ];
    }

    private function importSql(string $mysql_bin, string $sql_file_path, OutputInterface $output): void
    {
        $output->writeln('<info>OK</info>: Importing database data...');

        $mysql_host = DB_HOST;

        if (strpos($mysql_host, ':') !== false) {
            [$mysql_host, $mysql_port] = explode(':', $mysql_host);
        } else {
            $mysql_port = defined('DB_PORT') && DB_PORT ? DB_PORT : 3306;
        }

        $command_output = $this->executeCommand(
            sprintf(
                $mysql_bin . ' --host=%s --port=%s --user=%s --password=%s --default-character-set=%s %s < %s',
                escapeshellarg($mysql_host),
                escapeshellarg((string) $mysql_port),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg('utf8mb4'),
                escapeshellarg(DB_NAME),
                escapeshellarg($sql_file_path)
            )
        );

        if ($output->isVerbose()) {
            foreach ($command_output as $command_output_line) {
                $output->writeln('    ' . $command_output_line);
            }
        }
    }

    private function rsyncUploadedFiles(string $rsync_bin, string $upload_dir_path, OutputInterface $output)
    {
        $output->writeln('<info>OK</info>: Syncing files...');

        $command_output = $this->executeCommand(
            sprintf(
                $rsync_bin . ' -av %s %s',
                escapeshellarg(with_slash($upload_dir_path)),
                escapeshellarg(with_slash(UPLOAD_PATH))
            )
        );

        if ($output->isVerbose()) {
            foreach ($command_output as $command_output_line) {
                $output->writeln('    ' . $command_output_line);
            }
        }
    }

    private function cleanUp(string $import_work_path, OutputInterface $output)
    {
        $output->writeln('<info>OK</info>: Cleaning up...');
        delete_dir($import_work_path);
    }

    private function updateApplicationUniqueKey(string $application_unique_key, OutputInterface $output)
    {
        if (!$application_unique_key) {
            return;
        }

        if ($application_unique_key === APPLICATION_UNIQUE_KEY) {
            return;
        }

        $config_file_path = CONFIG_PATH . '/config.php';

        if (!is_file($config_file_path)) {
            throw new RuntimeException("Config file not found. Expected location: '{$config_file_path}'.");
        }

        $config_file_lines = file($config_file_path);

        foreach ($config_file_lines as $line_number => $config_file_line) {
            if (strpos($config_file_line, 'APPLICATION_UNIQUE_KEY') !== false
                && strpos($config_file_line, APPLICATION_UNIQUE_KEY) !== false) {
                $config_file_lines[$line_number] = 'const APPLICATION_UNIQUE_KEY = ' . var_export($application_unique_key, true) . ';';

                if ($this->writeConfigLines($config_file_path, $config_file_lines)) {
                    $output->writeln(
                        sprintf(
                            '<info>OK</info>: <comment>APPLICATION_UNIQUE_KEY</comment> changed from <comment>%s</comment> to <comment>%s</comment> in <comment>config/config.php</comment> file.',
                            APPLICATION_UNIQUE_KEY,
                            $application_unique_key
                        )
                    );
                } else {
                    $output->writeln(
                        sprintf(
                            '<error>Error</error>: Failed to change <comment>APPLICATION_UNIQUE_KEY</comment>. Please open <comment>config/config.php</comment> file and change the value to <comment>%s</comment>.',
                            $application_unique_key
                        )
                    );
                }

                return;
            }
        }

        $output->writeln(
            sprintf(
                '<error>Error</error>: <comment>APPLICATION_UNIQUE_KEY</comment> value not found in <comment>config/config.php</comment> file.',
                $application_unique_key
            )
        );
    }

    private function writeConfigLines(string $config_file_path, array $config_file_lines): bool
    {
        foreach ($config_file_lines as $k => $v) {
            $config_file_lines[$k] = trim($v);
        }

        return (bool) file_put_contents($config_file_path, implode("\r\n", $config_file_lines));
    }

    private function changeOwnerPassword(
        string $owner_email_address,
        string $new_password,
        OutputInterface $output
    ): void
    {
        if (empty($owner_email_address)) {
            return;
        }

        $owner_id = (int) DB::executeFirstCell(
            'SELECT `id` FROM `users` WHERE `email` = ? AND `type` = ?',
            $owner_email_address,
            Owner::class
        );

        if ($owner_id) {
            DB::execute(
                'UPDATE `users` SET `password` = ?, `password_hashed_with` = ? WHERE `id` = ?',
                AngieApplication::getContainer()->get(PasswordManagerInterface::class)->hash($new_password),
                PasswordManagerInterface::HASHED_WITH_PHP,
                $owner_id
            );

            $output->writeln(
                sprintf(
                    '<info>OK</info>: New password has been set for <comment>%s</comment> owner.',
                    $owner_email_address
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    '<warn>Warning</warn>: Owner with <comment>%s</comment> email address was not found. No password changes have been made.',
                    $owner_email_address
                )
            );
        }
    }

    private function communicateImport(int $number_of_users, OutputInterface $output): void
    {
        $user_rows = DB::execute('SELECT `id`, `type`, `email` FROM `users` ORDER BY `id`');

        if ($user_rows) {
            $output->writeln('');
            $output->writeln('Status of users table after data import:');
            $output->writeln('');

            $table = new Table($output);
            $table->setHeaders(
                [
                    '#',
                    'Role',
                    'Email',
                ]
            );

            foreach ($user_rows as $user_row) {
                $table->addRow(array_values($user_row));
            }

            $table->render();

            $output->writeln($this->getNumberOfUsersStatusMessage($number_of_users, count($user_rows)));
            $output->writeln('');
        } else {
            throw new RuntimeException('Users table is empty.');
        }
    }

    private function getNumberOfUsersStatusMessage($old_number_of_users, $new_number_of_users): string
    {
        switch ($old_number_of_users) {
            case 0:
                return sprintf(
                    'There was no users in the database prior to import. Now there are <comment>%s</comment> users.',
                    $new_number_of_users
                );
            case 1:
                return sprintf(
                    'There was <comment>one</comment> user in the database prior to import. Now there are <comment>%s</comment> users.',
                    $new_number_of_users
                );
            default:
                return sprintf(
                    'There were <comment>%d</comment> users prior to import. Now there are <comment>%s</comment> users.',
                    $old_number_of_users,
                    $new_number_of_users
                );
        }
    }

    private function downloadFromUrl(
        string $url,
        string $destination_file,
        callable $progress_callback,
        InputInterface $input
    ): string
    {
        $write_handle = fopen($destination_file, 'w+b');

        if (!$write_handle) {
            throw new RuntimeException('Cannot write update package to temporary folder');
        }

        $curl = curl_init($url);

        if ($curl_error = curl_error($curl)) {
            throw new RuntimeException("Operation failed with error: '$curl_error'.");
        }

        curl_setopt($curl, CURLOPT_FILE, $write_handle);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3000);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                "User-Agent: '" . AngieApplication::getName() . ' v' . AngieApplication::getVersion() . "'",
            ]
        );

        if ($input->getOption('skip-ssl-verification')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_CAINFO, CUSTOM_CA_FILE);
        }

        if ($input->getOption('proxy')) {
            curl_setopt($curl, CURLOPT_PROXY, $input->getOption('proxy'));
        }

        $response_headers = null;

        curl_setopt(
            $curl,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header_line) use (&$response_headers)
            {
                if ($curl) {
                    $response_headers .= $header_line;
                }

                return strlen($header_line);
            }
        );

        curl_setopt($curl, CURLOPT_NOPROGRESS, false);
        curl_setopt(
            $curl,
            CURLOPT_PROGRESSFUNCTION,
            function ($p1, $p2, $p3) use ($progress_callback) {
                // This IF is needed because in some cases we'll get resources as $p1
                // Info: http://stackoverflow.com/a/26622217/338473
                if (is_resource($p1) || is_a($p1, 'CurlHandle')) {
                    $download_size = $p2;
                    $downloaded_size = $p3;
                } else {
                    $download_size = $p1;
                    $downloaded_size = $p2;
                }

                $percents = 0;

                if ($download_size > 0) {
                    $percents = round($downloaded_size * 100 / $download_size);
                }

                call_user_func($progress_callback, $percents);
            }
        );

        curl_exec($curl);

        if ($curl_error = curl_error($curl)) {
            throw new RuntimeException("Operation failed with error: '$curl_error'");
        }

        $response_headers = $this->parseHeaders($response_headers);

        fclose($write_handle);

        curl_close($curl);
        unset($curl);

        if (empty($response_headers['status']) || $response_headers['status'] != 200) {
            throw new RuntimeException("HTTP {$response_headers['status']}, {$response_headers['status_text']}");
        }

        return $destination_file;
    }

    /**
     * Parse HTTP header, and return array with key => values.
     *
     * @param  string $headers
     * @return array
     */
    private function parseHeaders($headers)
    {
        $headers = explode("\n", trim($headers));
        $output = [];

        if ('HTTP' === substr($headers[0], 0, 4)) {
            [, $output['status'], $output['status_text']] = explode(' ', trim($headers[0]));
            unset($headers[0]);
        }

        foreach ($headers as $v) {
            $h = preg_split('/:\s*/', $v);
            $output[strtolower($h[0])] = trim($h[1]);
        }

        return $output;
    }
}
