<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\BatchUpdater;

use LogicException;
use TimeRecord;
use TimeRecords;
use User;

class TimeRecordBatchUpdater implements TimeRecordBatchUpdaterInterface
{
    /**
     * @return TimeRecord[]|array
     */
    public function batchUpdate(
        array $attributes,
        User $by,
        int ...$time_record_ids
    ): array
    {
        $result = [];

        if (empty($attributes)) {
            return $result;
        }

        $this->checkJobType($attributes);

        $time_records = $this->queryTimeRecords($time_record_ids);

        if ($time_records) {
            foreach ($time_records as $time_record) {
                if (!$time_record->canEdit($by)) {
                    continue;
                }

                $result[] = TimeRecords::update($time_record, $attributes);
            }
        }

        return $result;
    }

    private function checkJobType(array $attributes): void
    {
        foreach (array_keys($attributes) as $attribute) {
            if (!in_array($attribute, self::SUPPORTED_FIELDS)) {
                throw new LogicException(
                    sprintf(
                        'Field "%s" is not supported for batch edit.',
                        $attribute
                    )
                );
            }
        }

        if (array_key_exists('job_type_id', $attributes) && !$this->jobTypeExists($attributes['job_type_id'])) {
            throw new LogicException(
                sprintf(
                    'Job type "%s" does not exist.',
                    $attributes['job_type_id']
                )
            );
        }
    }

    private function jobTypeExists($job_type_id): bool
    {
        return !empty(\DB::executeFirstCell('SELECT COUNT(`id`) FROM `job_types` WHERE `id` = ?', $job_type_id));
    }

    /**
     * @return TimeRecord[]|iterable|null
     */
    private function queryTimeRecords(array $time_record_ids): ?iterable
    {
        if (empty($time_record_ids)) {
            return null;
        }

        return TimeRecords::find(
            [
                'conditions' => [
                    '`id` IN (?) AND `is_trashed` = ?',
                    $time_record_ids,
                    false,
                ],
            ]
        );
    }
}
