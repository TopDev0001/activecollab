<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\SystemExport;

use ActiveCollab\Module\System\Utils\ProjectExport\Exporter\ProjectJsonExporter;
use Angie\Error;
use Angie\Inflector;
use AngieApplication;
use DateTimeValue;
use DB;
use DirectoryCreateError;
use Exception;
use FileCreateError;
use PclZip;
use Project;
use Projects;
use RuntimeException;
use User;

class SystemExport implements SystemExportInterface
{
    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $work_folder_path = false;

    public function __construct(User $user, string $work_folder_path = null)
    {
        $this->user = $user;
        $this->timestamp = DateTimeValue::now()->getTimestamp();

        if ($work_folder_path) {
            $this->work_folder_path = $work_folder_path;
        }
    }

    public function export($pack = true, $delete_work_folder = true): string
    {
        $file_path = $this->getFilePath();
        $work_folder_path = $this->getWorkFolderPath();

        if (!is_file($file_path)) {
            $this->prepareWorkFolder();
            $this->writeCompanies();
            $this->writeTeams();
            $this->writeUsers();
            $this->writeProjects();
            $this->writeInvoices();
            $this->writeEstimates();

            if ($pack) {
                $this->pack($work_folder_path, $file_path, $delete_work_folder);
            }
        }

        return $pack ? $file_path : $work_folder_path;
    }

    /**
     * Return destination path of the exported file.
     */
    private function getFilePath(): string
    {
        return $this->getWorkFolderPath() . '.zip';
    }

    /**
     * Return work folder path.
     */
    private function getWorkFolderPath(): string
    {
        if ($this->work_folder_path === false) {
            $this->work_folder_path = WORK_PATH . '/' . AngieApplication::getAccountId() . '-system-export-' . date('Y-m-d-h-i-s', $this->timestamp);
        }

        return $this->work_folder_path;
    }

    /**
     * Prepare work folder path.
     */
    private function prepareWorkFolder()
    {
        $this->prepareDir($this->getWorkFolderPath());
    }

    private function prepareDir(string $path): void
    {
        if (!is_dir($path)) {
            $old_umask = umask(0000);
            $folder_created = mkdir($path, 0777);
            umask($old_umask);

            if (!$folder_created) {
                throw new DirectoryCreateError($path);
            }
        }
    }

    /**
     * Write companies.json.
     */
    private function writeCompanies()
    {
        if ($file_handle = fopen($this->getWorkFolderPath() . '/companies.json', 'a')) {
            if ($rows = DB::execute('SELECT * FROM companies ORDER BY id')) {
                $first = true;

                foreach ($rows as $row) {
                    if ($first) {
                        fwrite($file_handle, '[');
                        $first = false;
                    } else {
                        fwrite($file_handle, ',');
                    }

                    fwrite($file_handle, json_encode(array_merge([
                        'id' => $row['id'],
                        'is_archived' => (bool) $row['is_archived'],
                        'is_trashed' => (bool) $row['is_trashed'],
                        'name' => $row['name'],
                        'address' => $row['address'],
                        'homepage_url' => $row['homepage_url'],
                        'phone' => $row['phone'],
                    ], $this->actionOnByToArray('created', $row), $this->actionOnByToArray('updated', $row))));
                }

                fwrite($file_handle, ']');
            } else {
                fwrite($file_handle, '[]');
            }

            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getWorkFolderPath() . '/companies.json');
        }
    }

    /**
     * @param  string $action
     * @return array
     */
    private function actionOnByToArray($action, array &$row)
    {
        return [
            "{$action}_on" => isset($row["{$action}_on"]) && $row["{$action}_on"] ? strtotime($row["{$action}_on"]) : 0,
            "{$action}_by_id" => isset($row["{$action}_by_id"]) && $row["{$action}_by_id"] ? $row["{$action}_by_id"] : 0,
            "{$action}_by_name" => isset($row["{$action}_by_name"]) && $row["{$action}_by_name"] ? (string) $row["{$action}_by_name"] : '',
            "{$action}_by_email" => isset($row["{$action}_by_email"]) && $row["{$action}_by_email"] ? (string) $row["{$action}_by_email"] : '',
        ];
    }

    /**
     * Write teams.json.
     */
    private function writeTeams()
    {
        if ($file_handle = fopen($this->getWorkFolderPath() . '/teams.json', 'a')) {
            if ($rows = DB::execute('SELECT * FROM teams ORDER BY id')) {
                $first = true;

                foreach ($rows as $row) {
                    if ($first) {
                        fwrite($file_handle, '[');
                        $first = false;
                    } else {
                        fwrite($file_handle, ',');
                    }
                    fwrite($file_handle, json_encode(array_merge([
                        'id' => $row['id'],
                        'name' => $row['name'],
                    ], $this->actionOnByToArray('created', $row))));
                }
                fwrite($file_handle, ']');
            } else {
                fwrite($file_handle, '[]');
            }

            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getWorkFolderPath() . '/teams.json');
        }
    }

    /**
     * Write estimates.json.
     */
    private function writeEstimates()
    {
        if ($file_handle = fopen($this->getWorkFolderPath() . '/estimates.json', 'a')) {
            if ($estimates = DB::execute('SELECT * FROM estimates ORDER BY id')) {
                $first = true;

                foreach ($estimates as $estimate) {
                    if ($first) {
                        fwrite($file_handle, '[');
                        $first = false;
                    } else {
                        fwrite($file_handle, ',');
                    }

                    $estimate_items = DB::execute('SELECT * FROM invoice_items WHERE parent_id = ? AND parent_type = ? ORDER BY id', $estimate['id'], 'Estimate');

                    $items = [];
                    foreach ($estimate_items as $estimate_item) {
                        $items[] = [
                            'id' => $estimate_item['id'],
                            'discount_rate' => $estimate_item['discount_rate'],
                            'description' => (string) $estimate_item['description'],
                            'quantity' => $estimate_item['quantity'],
                            'unit_cost' => $estimate_item['unit_cost'],
                            'subtotal' => $estimate_item['subtotal'],
                            'discount' => $estimate_item['discount'],
                            'first_tax' => $estimate_item['first_tax'],
                            'second_tax' => $estimate_item['second_tax'],
                            'total' => $estimate_item['total'],
                        ];
                    }

                    fwrite($file_handle, json_encode(array_merge([
                        'id' => $estimate['id'],
                        'name' => $estimate['name'],
                        'company_name' => $estimate['company_name'],
                        'company_address' => $estimate['company_address'],
                        'discount_rate' => $estimate['discount_rate'],
                        'subtotal' => $estimate['subtotal'],
                        'discount' => $estimate['discount'],
                        'tax' => $estimate['tax'],
                        'total' => $estimate['total'],
                        'balance_due' => $estimate['balance_due'],
                        'paid_amount' => $estimate['paid_amount'],
                        'note' => (string) $estimate['note'],
                        'private_note' => $estimate['private_note'],
                        'status' => $estimate['status'],
                        'recipients' => (string) $estimate['recipients'],
                        'email_from_name' => $estimate['email_from_name'],
                        'email_from_email' => $estimate['email_from_email'],
                        'hash' => $estimate['hash'],
                        'is_trashed' => (bool) $estimate['is_trashed'],
                        'trashed_on' => $estimate['trashed_on'] ? strtotime($estimate['trashed_on']) : null,
                        'items' => $items,
                    ], $this->actionOnByToArray('created', $estimate), $this->actionOnByToArray('updated', $estimate), $this->actionOnByToArray('sent', $estimate))));
                }
                fwrite($file_handle, ']');
            } else {
                fwrite($file_handle, '[]');
            }

            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getWorkFolderPath() . '/estimate.json');
        }
    }

    /**
     * Write invoices.json.
     */
    private function writeInvoices()
    {
        if ($file_handle = fopen($this->getWorkFolderPath() . '/invoices.json', 'a')) {
            if ($invoices = DB::execute('SELECT * FROM invoices ORDER BY id')) {
                $first = true;

                foreach ($invoices as $invoice) {
                    if ($first) {
                        fwrite($file_handle, '[');
                        $first = false;
                    } else {
                        fwrite($file_handle, ',');
                    }

                    $invoice_items = DB::execute('SELECT * FROM invoice_items WHERE parent_id = ? AND parent_type = ? ORDER BY id', $invoice['id'], 'Invoice');

                    $items = [];
                    foreach ($invoice_items as $invoice_item) {
                        $items[] = [
                            'id' => $invoice_item['id'],
                            'discount_rate' => $invoice_item['discount_rate'],
                            'description' => (string) $invoice_item['description'],
                            'quantity' => $invoice_item['quantity'],
                            'unit_cost' => $invoice_item['unit_cost'],
                            'subtotal' => $invoice_item['subtotal'],
                            'discount' => $invoice_item['discount'],
                            'first_tax' => $invoice_item['first_tax'],
                            'second_tax' => $invoice_item['second_tax'],
                            'total' => $invoice_item['total'],
                        ];
                    }

                    fwrite(
                        $file_handle,
                        json_encode(
                            array_merge(
                                [
                                    'id' => $invoice['id'],
                                    'number' => $invoice['number'],
                                    'purchase_order_number' => $invoice['purchase_order_number'],
                                    'company_name' => $invoice['company_name'],
                                    'company_address' => $invoice['company_address'],
                                    'project_id' => $invoice['project_id'],
                                    'discount_rate' => $invoice['discount_rate'],
                                    'subtotal' => $invoice['subtotal'],
                                    'discount' => $invoice['discount'],
                                    'tax' => $invoice['tax'],
                                    'total' => $invoice['total'],
                                    'balance_due' => $invoice['balance_due'],
                                    'paid_amount' => $invoice['paid_amount'],
                                    'last_payment_on' => $invoice['last_payment_on'],
                                    'note' => (string) $invoice['note'],
                                    'private_note' => (string) $invoice['private_note'],
                                    'due_on' => $invoice['due_on'],
                                    'issued_on' => $invoice['issued_on'],
                                    'sent_on' => $invoice['sent_on'],
                                    'is_canceled' => (bool) $invoice['is_canceled'],
                                    'is_muted' => (bool) $invoice['is_muted'],
                                    'hash' => $invoice['hash'],
                                    'is_trashed' => (bool) $invoice['is_trashed'],
                                    'trashed_on' => $invoice['trashed_on'],
                                    'items' => $items,
                                ],
                                $this->actionOnByToArray('created', $invoice),
                                $this->actionOnByToArray('closed', $invoice),
                            ),
                        ),
                    );
                }
                fwrite($file_handle, ']');
            } else {
                fwrite($file_handle, '[]');
            }

            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getWorkFolderPath() . '/invoices.json');
        }
    }

    private function writeUsers(): void
    {
        if ($file_handle = fopen($this->getWorkFolderPath() . '/users.json', 'a')) {
            if ($rows = DB::execute('SELECT * FROM users ORDER BY id')) {
                $first = true;

                foreach ($rows as $row) {
                    if ($first) {
                        fwrite($file_handle, '[');
                        $first = false;
                    } else {
                        fwrite($file_handle, ',');
                    }

                    fwrite(
                        $file_handle,
                        json_encode(
                            array_merge(
                                [
                                    'id' => $row['id'],
                                    'type' => $row['type'],
                                    'company_id' => $row['company_id'],
                                    'is_archived' => (bool) $row['is_archived'],
                                    'is_trashed' => $row['is_trashed'],
                                    'first_name' => $row['first_name'],
                                    'last_name' => $row['last_name'],
                                    'title' => $row['title'],
                                    'email' => $row['email'],
                                    'phone' => $row['phone'],
                                    'im_type' => $row['im_type'],
                                    'im_handle' => $row['im_handle'],
                                ],
                                $this->actionOnByToArray('created', $row),
                            ),
                        ),
                    );
                }
                fwrite($file_handle, ']');
            } else {
                fwrite($file_handle, '[]');
            }

            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getWorkFolderPath() . '/users.json');
        }
    }

    private function writeProjects(): void
    {
        $project_files = [];
        $projects_path = $this->getWorkFolderPath() . '/projects';

        $this->prepareDir($projects_path);

        if ($projects = Projects::find()) {
            foreach ($projects as $project) {
                if ($project instanceof Project) {
                    $project_export = new ProjectJsonExporter(
                        $project,
                        $this->user,
                        null,
                        true,
                    );

                    $project_export->export(true);

                    $destination_path = $projects_path . '/' . $this->getProjectFileName($project);

                    if (copy($project_export->getFilePath(), $destination_path)) {
                        unlink($project_export->getFilePath());
                    } else {
                        throw new Exception(
                            sprintf(
                                'Failed to copy project file fro %s to %s.',
                                $project_export->getFilePath(),
                                $destination_path,
                            ),
                        );
                    }
                    $project_files = array_merge($project_files, $project_export->getFileLocations());
                }
            }
        }
        $this->writeProjectFiles($project_files);
    }

    private function getProjectFileName(Project $project): string
    {
        return Inflector::slug(Inflector::transliterate($project->getName())) . '-' . $project->getId() . '.zip';
    }

    private function writeProjectFiles(array $project_files): void
    {
        $projects_file_path = $this->getWorkFolderPath() . '/upload';

        $this->prepareDir($projects_file_path);

        if ($file_handle = fopen($this->getWorkFolderPath() . '/upload/files_index.json', 'a')) {
            if (!empty($project_files)) {
                fwrite($file_handle, json_encode($project_files));
            } else {
                fwrite($file_handle, '[]');
            }

            fclose($file_handle);
        } else {
            throw new FileCreateError($this->getWorkFolderPath() . '/upload/files_index.json');
        }
    }

    private function pack(string $work_path, string $file_path, bool $delete_work_folder = true): void
    {
        if (is_file($file_path)) {
            @unlink($file_path);
        }

        if (is_file($file_path)) {
            throw new RuntimeException(
                sprintf(
                    'Failed to delete path at %s',
                    $this->getSafeToCommunicateDirPath($file_path, ENVIRONMENT_PATH),
                ),
            );
        }

        $zip = new PclZip($file_path);

        if (!$zip->add(get_files($work_path, null, true), PCLZIP_OPT_REMOVE_PATH, WORK_PATH)) {
            throw new Error('Could not pack files');
        }

        if (DIRECTORY_SEPARATOR != '\\') {
            @chmod($file_path, 0777);
        }

        if ($delete_work_folder) {
            if (!safe_delete_dir($work_path, WORK_PATH, true)) {
                throw new RuntimeException(
                    sprintf(
                        'Failed to delete directory at %s',
                        $this->getSafeToCommunicateDirPath($work_path, ENVIRONMENT_PATH),
                    ),
                );
            }
        }
    }

    private function getSafeToCommunicateDirPath(string $path, string $root_path): string
    {
        return substr($path, strlen($root_path));
    }
}
