<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory;

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use DateValue;

interface VariableProcessorFactoryInterface
{
    public function createForTaskList(DateValue $reference_date = null): VariableProcessorInterface;
    public function createForTask(DateValue $reference_date = null): VariableProcessorInterface;
    public function createForSubtask(DateValue $reference_date = null): VariableProcessorInterface;
    public function createForDiscussion(DateValue $reference_date = null): VariableProcessorInterface;
    public function createForNote(DateValue $reference_date = null): VariableProcessorInterface;
    public function createForInvoice(DateValue $reference_date = null): VariableProcessorInterface;
}
