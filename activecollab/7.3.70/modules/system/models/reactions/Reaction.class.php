<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReactionEvents\ReactionDeletedEvent;

abstract class Reaction extends BaseReaction implements RoutingContextInterface
{
    public function getRoutingContext(): string
    {
        return 'reaction';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'reaction_id' => $this->getId(),
        ];
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'parent_id' => $this->getParentId(),
                'parent_type' => $this->getParentType(),
            ]
        );
    }

    public function touchParentOnPropertyChange(): ?array
    {
        return [
            'type',
        ];
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting reaction @ ' . __CLASS__);

            DataObjectPool::announce(new ReactionDeletedEvent($this));

            parent::delete($bulk);

            $parent = $this->getParent();

            if ($parent instanceof Comment) {
                Notifications::deleteByParentAndAdditionalProperty(
                    $parent->getParent(),
                    'reaction_id',
                    $this->getId()
                );
            }

            DB::commit('Reaction deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete reaction @ ' . __CLASS__);

            throw $e;
        }
    }
}
