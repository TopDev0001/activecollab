<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

trait IChildImplementation
{
    use RoutingContextImplementation;

    private bool $prevent_touch_on_next_delete = false;

    public function registerIChildImplementation()
    {
        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $parent = $this->getParent();

                if ($parent instanceof ApplicationObject) {
                    $result['parent_type'] = get_class($parent);
                    $result['parent_id'] = $parent->getId();
                } else {
                    $result['parent_type'] = null;
                    $result['parent_id'] = null;
                }
            }
        );

        if (!empty($this->touchParentOnPropertyChange())) {
            $this->registerEventHandler(
                'on_after_save',
                function ($was_new, $modifications) {
                    $parent = $this->getParent();

                    if ($parent instanceof ApplicationObject) {
                        $touch = $was_new;

                        if (empty($touch)) {
                            foreach ($this->touchParentOnPropertyChange() as $property) {
                                if (isset($modifications[$property])) {
                                    $touch = true;
                                    break;
                                }
                            }
                        }

                        if ($touch) {
                            $parent->touch();
                        }
                    }

                    if (isset($modifications['parent_type']) || isset($modifications['parent_id'])) {
                        $old_parent_type = $this->getParentType();
                        $old_parent_id = $this->getParentId();

                        if (isset($modifications['parent_type'])) {
                            $old_parent_type = $modifications['parent_type'][0];
                        }

                        if (isset($modifications['parent_id'])) {
                            $old_parent_id = $modifications['parent_id'][0];
                        }

                        $old_parent = DataObjectPool::get($old_parent_type, $old_parent_id);

                        if ($old_parent instanceof DataObject) {
                            $old_parent->touch();
                        }
                    }
                }
            );
        }

        $this->registerEventHandler(
            'on_after_delete',
            function () {
                if ($this->prevent_touch_on_next_delete) {
                    $this->prevent_touch_on_next_delete = false;
                } else {
                    if ($this->getParent() instanceof ApplicationObject) {
                        $this->getParent()->touch();
                    }
                }
            }
        );

        if (!$this->isParentOptional()) {
            $this->registerEventHandler(
                'on_validate',
                function (ValidationErrors & $errors) {
                    if (!$this->validatePresenceOf('parent_type') || !$this->validatePresenceOf('parent_id')) {
                        $errors->addError('Parent is required', 'parent');
                    }
                }
            );
        }

        if ($this instanceof IHistory) {
            $this->addHistoryFields('parent_type', 'parent_id');
        }
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    public function getParent()
    {
        return DataObjectPool::get($this->getParentType(), $this->getParentId());
    }

    /**
     * Return parent type.
     *
     * @return string
     */
    abstract public function getParentType();

    /**
     * Return parent ID.
     *
     * @return int
     */
    abstract public function getParentId();

    // ---------------------------------------------------
    //  Routing context implementation
    // ---------------------------------------------------

    abstract public function touchParentOnPropertyChange(): ?array;

    public function isParentOptional(): bool
    {
        return true;
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    abstract public function validatePresenceOf($field, $min_value = null, $modifier = null);

    public function setParent($parent, $save = false)
    {
        if ($parent instanceof DataObject) {
            $this->setParentType(get_class($parent));
            $this->setParentId($parent->getId());
        } elseif ($parent === null) {
            $this->setParentType(null);
            $this->setParentId(0);
        } else {
            throw new InvalidInstanceError('parent', $parent, 'DataObject');
        }

        if ($save) {
            $this->save();
        }

        return $parent;
    }

    /**
     * Set parent type.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setParentType($value);

    /**
     * Set parent ID.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setParentId($value);

    public function isParent(ApplicationObject $parent): bool
    {
        return $this->getParentType() == get_class($parent) && $this->getParentId() == $parent->getId();
    }

    public function getRoutingContext(): string
    {
        return AngieApplication::cache()->getByObject(
            $this,
            [
                'routing',
                'context',
            ],
            function () {
                $parent = $this->getParent();
                $type_name = $this->getBaseTypeName();

                return $parent instanceof RoutingContextInterface
                    ? $parent->getRoutingContext() . '_' . $type_name
                    : $type_name;
            }
        );
    }

    public function getRoutingContextParams(): array
    {
        return AngieApplication::cache()->getByObject(
            $this,
            [
                'routing',
                'params',
            ],
            function () {
                $parent = $this->getParent();
                $type_name = $this->getBaseTypeName();

                if ($parent instanceof RoutingContextInterface) {
                    $params = $parent->getRoutingContextParams();

                    if (empty($params)) {
                        $params = [];
                    }

                    $params["{$type_name}_id"] = $this->getId();
                } else {
                    $params = ["{$type_name}_id" => $this->getId()];
                }

                return $params;
            }
        );
    }

    public function preventTouchOnNextDelete()
    {
        $this->prevent_touch_on_next_delete = true;
    }
}
