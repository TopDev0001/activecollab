<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use Angie\Reports\Report;
use Angie\Reports\Report as ReportInterface;
use DataFilter;
use ReflectionClass;
use User;

final class Reports
{
    private static array $available_reports_for = [];

    /**
     * Return report instance based on type and attributes.
     */
    public static function getReport(string $type, array $attributes = []): ?ReportInterface
    {
        /** @var Report $report */
        $report = null;

        if (self::isValidReportType($type)) {
            if (isset($attributes['id']) && $attributes['id']) {
                $report = new $type($attributes['id']);

                /** @var DataFilter $report */
                if ($report->isNew()) {
                    return null; // Failed to load report
                }
            } else {
                $report = new $type();
                $report->setAttributes($attributes);
            }
        }

        return $report;
    }

    private static function isValidReportType(string $type): bool
    {
        return class_exists($type, true)
            && (new ReflectionClass($type))->implementsInterface(ReportInterface::class);
    }

    public static function getAvailableReportsFor(User $user): array
    {
        if (!array_key_exists($user->getId(), self::$available_reports_for)) {
            $reports = self::prepareCollection();

            Events::trigger('on_reports', [&$reports]);

            self::$available_reports_for[$user->getId()] = $reports;
        }

        return self::$available_reports_for[$user->getId()];
    }

    private static function prepareCollection(): array
    {
        $collection = [];

        foreach (self::getSections() as $k => $v) {
            $collection[$k] = [
                'label' => $v,
                'reports' => new NamedList(),
            ];
        }

        return $collection;
    }

    private static ?NamedList $sections = null;

    private static function getSections(): NamedList
    {
        if (self::$sections === null) {
            self::$sections = new NamedList();

            Events::trigger('on_report_sections', [&self::$sections]);

            self::$sections->add('custom', lang('Custom'));
        }

        return self::$sections;
    }
}
