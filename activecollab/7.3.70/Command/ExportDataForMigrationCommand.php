<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

// -------------------------------------------- //
// PLEASE KEEP THIS COMMAND PHP 5.6 COMPATIBLE! //
// -------------------------------------------- //

namespace ActiveCollab\Command;

use Angie\Command\Command;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDataForMigrationCommand extends Command
{
    const SQL_EXPORT_FILE = 'db_data.sql';
    const ZIP_EXPORT_FILE = 'export_data.zip';
    const UPLOAD_DIR_NAME = 'upload';

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Prepare data for migration to Cloud')
            ->addOption(
                'mysqldump_bin',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to mysqldump binary.',
                'mysqldump'
            )
            ->addOption(
                'zip_bin',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to zip binary.',
                'zip'
            )
            ->addOption(
                'perl_bin',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to perl binary.',
                'perl'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'For new export if older export is found.'
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

            $output->writeln('<info>OK</info>: Increasing memory limit...');
            ini_set('memory_limit', '-1');

            $export_path = $this->getExportPath($input, $output);

            if (empty($export_path)) {
                return 0;
            }

            $mysqldump_bin = $this->getMySqlDumpBin($input, $output);
            $unzip_bin = $this->getZipBin($input, $output);
            $perl_bin = $this->getPerlBinary($input, $output);

            $sql_file_path = $export_path . '/' . self::SQL_EXPORT_FILE;
            $zip_file_path = $export_path . '/' . self::ZIP_EXPORT_FILE;

            $this->exportSql($mysqldump_bin, $perl_bin, $sql_file_path, $output);
            $this->packFiles($unzip_bin, $zip_file_path, $output);

            $public_export_path = $this->cleanUp($sql_file_path, $zip_file_path, $output);

            $output->writeln('');
            $output->writeln('<info>OK</info>: Export has been completed:');
            $output->writeln('');
            $output->writeln(
                sprintf(
                    '    <comment>*</comment> Download URL: <comment>%s</comment>',
                    ROOT_URL . '/public/' . basename($public_export_path)
                )
            );
            $output->writeln(
                sprintf(
                    '    <comment>*</comment> Application unique key: <comment>%s</comment>',
                    APPLICATION_UNIQUE_KEY
                )
            );
            $output->writeln('');

            return 0;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    /**
     * @return string
     */
    private function getExportPath(InputInterface $input, OutputInterface $output)
    {
        $current_working_directory = without_slash(getcwd());

        $output->writeln(
            sprintf(
                '<info>OK</info>: Current working directory is <comment>%s</comment>.',
                $current_working_directory
            )
        );

        foreach (['public', 'upload'] as $required_dir_name) {
            $required_dir_path = $current_working_directory . '/' . $required_dir_name;

            if (is_dir($required_dir_path)) {
                $output->writeln(
                    sprintf(
                        '<info>OK</info>: /%s directory found at <comment>%s</comment>.',
                        $required_dir_name,
                        $required_dir_path
                    )
                );
            } else {
                $output->writeln(
                    sprintf(
                        '<error>Error</error>: /%s directory expected at <comment>%s</comment>, but not present...',
                        $required_dir_name,
                        $required_dir_path
                    )
                );

                return '';
            }
        }

        $forced = $input->getOption('force');

        foreach ([self::SQL_EXPORT_FILE, self::ZIP_EXPORT_FILE] as $export_file) {
            $existing_export_file = "$current_working_directory/$export_file";

            if (is_file($export_file) && !$forced) {
                $output->writeln(
                    sprintf(
                        '<error>Error</error>: File <comment>%s</comment> found. Aborting...',
                        $existing_export_file
                    )
                );

                return '';
            }
        }

        return $current_working_directory;
    }

    /**
     * @return string
     */
    private function getMySqlDumpBin(InputInterface $input, OutputInterface $output)
    {
        $mysqldump_bin = $input->getOption('mysqldump_bin');

        $command_output = $this->executeCommand("{$mysqldump_bin} -V");

        $output->writeln(
            sprintf(
                '<info>OK</info>: MySQL binnary is <comment>%s</comment>. Version info: <comment>%s</comment>.',
                $mysqldump_bin,
                substr($command_output[0], 0, strpos($command_output[0], ','))
            )
        );

        return $mysqldump_bin;
    }

    /**
     * @return string
     */
    private function getZipBin(InputInterface $input, OutputInterface $output)
    {
        $zip_bin = $input->getOption('zip_bin');

        $command_output = $this->executeCommand("{$zip_bin} -v");

        $output->writeln(
            sprintf(
                '<info>OK</info>: Zip binary is <comment>%s</comment>. Version info: <comment>%s</comment>.',
                $zip_bin,
                trim(str_replace('This is', '', explode('(', $command_output[1])[0]))
            )
        );

        return $zip_bin;
    }

    /**
     * @return string
     */
    private function getPerlBinary(InputInterface $input, OutputInterface $output)
    {
        $perl_bin = $input->getOption('perl_bin');

        $command_output = $this->executeCommand("{$perl_bin} -v");

        $matches = [];

        if (!empty($command_output[1]) && preg_match('/\((.*?)\)/', $command_output[1], $matches)) {
            $output->writeln(
                sprintf(
                    '<info>OK</info>: Perl binary is <comment>%s</comment>. Version info: <comment>%s</comment>.',
                    $perl_bin,
                    trim($matches[0], '()')
                )
            );
        }

        return $perl_bin;
    }

    /**
     * @param string $mysqldump_bin
     * @param string $perl_bin
     * @param string $sql_file_path
     */
    private function exportSql($mysqldump_bin, $perl_bin, $sql_file_path, OutputInterface $output)
    {
        $output->writeln('<info>OK</info>: Exporting database data...');

        $mysql_host = DB_HOST;

        if (strpos($mysql_host, ':') !== false) {
            [$mysql_host, $mysql_port] = explode(':', $mysql_host);
        } else {
            $mysql_port = defined('DB_PORT') && DB_PORT ? DB_PORT : 3306;
        }

        // Run mysqldump and pipe it through regexp that will turn DEFINER declarations to comments
        $command_output = $this->executeCommand(
            sprintf(
                $mysqldump_bin . ' --host=%s --port=%s --user=%s --password=%s --default-character-set=%s %s | %s -pe %s > %s',
                escapeshellarg($mysql_host),
                escapeshellarg((string) $mysql_port),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg('utf8mb4'),
                escapeshellarg(DB_NAME),
                $perl_bin,
                escapeshellarg('s/\!\d+ DEFINER/DEFINER/'),
                escapeshellarg($sql_file_path)
            )
        );

        if ($output->isVerbose()) {
            foreach ($command_output as $command_output_line) {
                $output->writeln('    ' . $command_output_line);
            }
        }
    }

    /**
     * @param string $zip_bin
     * @param string $zip_file_path
     */
    private function packFiles($zip_bin, $zip_file_path, OutputInterface $output)
    {
        $output->writeln('<info>OK</info>: Packing files...');

        $command_output = $this->executeCommand(
            sprintf(
                $zip_bin . ' -r %s %s %s',
                escapeshellarg($zip_file_path),
                escapeshellarg(self::SQL_EXPORT_FILE),
                escapeshellarg(self::UPLOAD_DIR_NAME)
            )
        );

        if ($output->isVerbose()) {
            foreach ($command_output as $command_output_line) {
                $output->writeln('    ' . $command_output_line);
            }
        }
    }

    /**
     * @param  string $sql_file_path
     * @param  string $zip_file_path
     * @return string
     */
    private function cleanUp($sql_file_path, $zip_file_path, OutputInterface $output)
    {
        $output->writeln('<info>OK</info>: Cleaning up...');
        unlink($sql_file_path);

        $target_path = dirname($zip_file_path) . '/public/data-export-' . sha1(ROOT . time()) . '.zip';

        if (rename($zip_file_path, $target_path)) {
            $output->writeln(
                sprintf(
                    '<info>OK</info>: Export archive moved to <comment>%s</comment>.',
                    $target_path
                )
            );

            return $target_path;
        } else {
            throw new RuntimeException('Failed to move export package to /public directory');
        }
    }
}
