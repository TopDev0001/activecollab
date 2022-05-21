<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ReorderService;

use ActiveCollab\User\UserInterface;
use LogicException;
use RuntimeException;

class ReorderService implements ReorderServiceInterface
{
    private ?OrderableDataManagerInterface $data_manager = null;

    public function setDataManager(OrderableDataManagerInterface $data_manager): ReorderServiceInterface
    {
        $this->data_manager = $data_manager;

        return $this;
    }

    private function getDataManager(): OrderableDataManagerInterface
    {
        if (!$this->data_manager instanceof OrderableDataManagerInterface) {
            throw new RuntimeException('Data manager not found.');
        }

        return $this->data_manager;
    }

    public function reorder(array $changes, UserInterface $user): array
    {
        if (empty($changes)) {
            throw new LogicException('There are no changes.');
        }

        ksort($changes);
        $items = $this->getDataManager()->getItemsByPosition($user);

        if (empty($items)) {
            throw new LogicException('There are no items.');
        }

        foreach ($changes as $change) {
            $source = $this->getItemId($change['source'], $items);
            $target = $this->getItemId($change['target'], $items);

            $tmp = [$items[$source]];
            unset($items[$source]);

            array_splice(
                $items,
                $target,
                0,
                $tmp
            );
        }

        $position = 1;
        $to_update = [];
        $ordered_items = [];
        foreach ($items as $item) {
            if ((int) $item['position'] !== $position) {
                $to_update[] = [
                    'id' => $item['id'],
                    'new_position' => $position,
                ];
            }

            $ordered_items[$position] = $item['id'];

            $position++;
        }

        $this->getDataManager()->updatePositions($to_update);

        return $ordered_items;
    }

    private function getItemId($source, array $items): int
    {
        $id = array_search($source, array_column($items, 'id'));

        if (!is_int($id)) {
            throw new LogicException("Failed to find item #{$source}.");
        }

        return $id;
    }
}
