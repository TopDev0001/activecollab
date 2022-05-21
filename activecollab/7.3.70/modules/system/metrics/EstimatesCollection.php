<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use DB;

final class EstimatesCollection extends Collection
{
    public function getValueFor(DateValue $date): ResultInterface
    {
        $total_estimates = DB::execute(
            'SELECT count(*) AS number, status from estimates GROUP BY status'
        );
        if ($total_estimates) {
            $total_estimates->setCasting('number', 'int');
            $total_estimates = $total_estimates->toArrayIndexedBy('status');
        }

        return $this->produceResult(
            [
                'total' => array_sum(array_column($total_estimates ?: [], 'number')),
                'by_status' => [
                    'draft' => $total_estimates['draft']['number'] ?? 0,
                    'sent' => $total_estimates['sent']['number'] ?? 0,
                    'won' => $total_estimates['won']['number'] ?? 0,
                    'lost' => $total_estimates['lost']['number'] ?? 0,
                ],
            ],
            $date
        );
    }
}
