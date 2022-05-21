<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Modules\AngieFramework;

interface AngieFrameworkModelInterface
{
    /**
     * Add table that is loaded from a definition AMQPChannel.
     *
     * @param  string  $table_name
     * @return DBTable
     */
    public function addTableFromFile($table_name);

    /**
     * Add table to the list of tables used by this framework or model.
     *
     * @return DBTable
     */
    public function addTable(DBTable $table);

    /**
     * Load table from a file file.
     *
     * @param  string       $table_name
     * @return DBTable
     * @throws FileDnxError
     */
    public function loadTableDefinion($table_name);

    /**
     * Add model from a file.
     *
     * @param  string                     $table_name
     * @return AngieFrameworkModelBuilder
     */
    public function addModelFromFile($table_name);

    /**
     * Add model.
     *
     * @return AngieFrameworkModelBuilder
     */
    public function addModel(DBTable $table);

    /**
     * Return all tables defined by this model.
     *
     * @return array
     */
    public function getTables();

    /**
     * Return single table.
     *
     * @param  string            $name
     * @return DBTable
     * @throws InvalidParamError
     */
    public function getTable($name);

    /**
     * Return parent module or framework.
     *
     * @return AngieFramework
     */
    public function getParent();

    /**
     * Return all model builders defined by this model.
     *
     * @return AngieFrameworkModelBuilder[]
     */
    public function getModelBuilders();

    /**
     * Return specific model builder.
     *
     * @param  string                     $for_table_name
     * @return AngieFrameworkModelBuilder
     * @throws InvalidParamError
     */
    public function getModelBuilder($for_table_name);

    /**
     * Create framework tables.
     */
    public function createTables();

    /**
     * Enter description here...
     */
    public function dropTables();

    /**
     * Load initial framework data.
     */
    public function loadInitialData();

    public function loadTableData(string $table, array $rows): void;

    public function getUpgradeSteps();

    /**
     * Execute specified upgrade step.
     *
     * This function validates step name before executing it
     *
     * @param string $step_name
     */
    public function executeUpgradeStep($step_name);
}
