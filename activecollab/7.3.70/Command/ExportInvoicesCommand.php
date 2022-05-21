<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use Invoice;
use Invoices;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package ActiveCollab\Command
 */
class ExportInvoicesCommand extends Command
{
    public function configure()
    {
        parent::configure();

        $this
            ->addOption('set-memory-limit', '', InputOption::VALUE_REQUIRED, 'Preparing PDFs is memory intensive. This argument controls how much memory export invoices command will reserve', '2048M')
            ->setDescription('Export all invoices as PDF files grouped by client name and year');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var Invoice[] $invoices */
            if ($invoices = Invoices::find()) {
                $memory_limit = $input->getOption('set-memory-limit');

                if (!empty($memory_limit)) {
                    $output->writeln("<info>OK</info>: Setting memory limit to <comment>$memory_limit</comment>");
                    ini_set('memory_limit', $memory_limit);
                }

                $output->writeln('<info>OK</info>: Exporting <comment>' . count($invoices) . '</comment> invoices');
                $output->writeln('');

                $export_target = WORK_PATH . '/' . AngieApplication::getAccountId() . '-invoices-export';

                $this->makeSureThatDirExists($export_target);

                foreach ($invoices as $invoice) {
                    $client_name = $invoice->getCompanyName();
                    $issue_year = $invoice->getIssuedOn() ? $invoice->getIssuedOn()->getYear() : (int) date('Y');

                    $this->makeSureThatDirExists("$export_target/$client_name");
                    $this->makeSureThatDirExists("$export_target/$client_name/$issue_year");

                    $invoice_file = $invoice->exportToFile();

                    if (is_file($invoice_file)) {
                        $safe_invoice_filename = str_replace(['/', '\\'], ['-', '-'], "{$invoice->getNumber()}.pdf");
                        $relative_target_path = "$client_name/$issue_year/$safe_invoice_filename";

                        if (rename($invoice_file, "$export_target/$relative_target_path")) {
                            $output->writeln("    <comment>*</comment> Invoice <comment>{$invoice->getNumber()}</comment> has been exported to <comment>$relative_target_path</comment>");
                        } else {
                            throw new RuntimeException("Failed to move exported invoice from <comment>$invoice_file</comment> to <comment>$relative_target_path</comment>");
                        }
                    } else {
                        throw new RuntimeException("Failed to export invoice #{$invoice->getNumber()}");
                    }
                }

                $output->writeln('');

                return $this->success("Invoices have been exported to <comment>$export_target</comment> directory", $input, $output);
            } else {
                return $this->success('Nothing to export', $input, $output);
            }
        } catch (\Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    /**
     * @param string $dir_path
     */
    private function makeSureThatDirExists($dir_path)
    {
        if (!is_dir($dir_path)) {
            $old_umask = umask(0000);
            $dir_created = mkdir($dir_path, 0777);
            umask($old_umask);

            if (!$dir_created) {
                throw new RuntimeException("Failed to create directory '$dir_path'");
            }
        }
    }
}
