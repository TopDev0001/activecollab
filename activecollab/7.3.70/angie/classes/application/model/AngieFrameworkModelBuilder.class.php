<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Model generator class.
 *
 * @package angie.library.application
 * @subpackage application
 */
class AngieFrameworkModelBuilder
{
    /**
     * Model that this builder instance belongs to.
     *
     * @var AngieFrameworkModel
     */
    protected $model;

    /**
     * Name of the table that this model builder is added to.
     *
     * @var DBTable
     */
    protected $table;

    /**
     * Name of the class that base object extends.
     *
     * @var string
     */
    protected $base_object_extends = 'ApplicationObject';

    /**
     * Name of the class that base manager extends.
     *
     * @var string
     */
    protected $base_manager_extends = 'DataManager';

    /**
     * Obect is abstract.
     *
     * @var bool
     */
    protected $object_is_abstract = false;

    /**
     * Manager is abstract.
     *
     * @var bool
     */
    protected $manager_is_abstract = false;

    /**
     * Construct instances based on class name stored in a field.
     *
     * @var string
     */
    protected $type_from_field;

    /**
     * Generate permissions in model class.
     *
     * @var bool
     */
    protected $generate_permissions = false;

    /**
     * Generate view edit and delete URL-s.
     *
     * @var bool
     */
    protected $generate_urls = false;

    /**
     * Value used for ordering records.
     *
     * @var string
     */
    protected $order_by;

    /**
     * Name of the module where this model is injected to (works only for framework models).
     *
     * @var string
     */
    protected $inject_into = 'system';

    /**
     * Model associations.
     *
     * @var array
     */
    protected $associations = [];
    /**
     * Model traits.
     *
     * @var array
     */
    private $model_traits = [];

    // ---------------------------------------------------
    //  Interface implementation
    // ---------------------------------------------------
    /**
     * Trait conflict resolutions.
     *
     * @var array
     */
    private $trait_tweaks = [];

    /**
     * Construct new model builder instance.
     *
     * @throws InvalidInstanceError
     */
    public function __construct(AngieFrameworkModel $model, DBTable $table)
    {
        $this->model = $model;

        if ($table instanceof DBTable) {
            $this->table = $table;
        } else {
            throw new InvalidInstanceError('table', $table, 'DBTable');
        }
    }

    /**
     * Return model traits.
     *
     * @return array
     */
    public function getModelTraits()
    {
        $result = $this->model_traits;

        foreach ($this->table->getModelTraits() as $k => $v) {
            $result[$k] = $v;
        }

        return $result;
    }

    /**
     * Return model trait tweaks.
     *
     * @return array
     */
    public function getModelTraitTweaks()
    {
        return $this->trait_tweaks;
    }

    /**
     * Resolve trait conflict.
     *
     * @param  string                     $tweak
     * @return AngieFrameworkModelBuilder
     */
    public function &addModelTraitTweak($tweak)
    {
        $this->trait_tweaks[] = $tweak;

        return $this;
    }

    /**
     * Implement comments.
     *
     * @param  bool  $include_subscriptions
     * @param  bool  $include_incoming_mail
     * @return $this
     */
    public function &implementComments($include_subscriptions = false, $include_incoming_mail = false)
    {
        if ($include_subscriptions && empty($this->model_traits[ISubscriptions::class])) {
            $this->implementSubscriptions();
        }

        if ($include_incoming_mail) {
            $this->addModelTrait(IIncomingMail::class);
        }

        $this->addModelTrait(IComments::class, ICommentsImplementation::class);

        return $this;
    }

    // ---------------------------------------------------
    //  Interfaces
    // ---------------------------------------------------

    /**
     * Implement subscriptions.
     *
     * @return $this
     */
    public function &implementSubscriptions()
    {
        $this->addModelTrait(ISubscriptions::class, ISubscriptionsImplementation::class);

        return $this;
    }

    /**
     * Add model trait.
     *
     * @param  string            $interface
     * @param  string            $implementation
     * @return $this
     * @throws InvalidParamError
     */
    public function &addModelTrait($interface = null, $implementation = null)
    {
        if (is_array($interface)) {
            foreach ($interface as $k => $v) {
                $this->addModelTrait($k, $v);
            }

        // Interface and implementation (optional)
        } else {
            if ($interface) {
                if (empty($this->model_traits[$interface])) {
                    $this->model_traits[$interface] = [];
                }

                $this->model_traits[$interface][] = $implementation;

            // Just a trait
            } elseif ($interface === null && $implementation) {
                if (empty($this->model_traits['--just-paste-trait--'])) {
                    $this->model_traits['--just-paste-trait--'] = [];
                }

                $this->model_traits['--just-paste-trait--'][] = $implementation;

            // Invalid input
            } else {
                throw new InvalidParamError('interface', $interface);
            }
        }

        return $this;
    }

    /**
     * Implement attachments.
     *
     * @return $this
     */
    public function &implementAttachments()
    {
        $this->addModelTrait('IAttachments', 'IAttachmentsImplementation');

        return $this;
    }

    /**
     * Implement attachments.
     *
     * @return $this
     */
    public function &implementAssignees()
    {
        $this->addModelTrait('IAssignees', 'IAssigneesImplementation');

        return $this;
    }

    /**
     * Implement reminders.
     *
     * @return $this
     */
    public function &implementReminders()
    {
        $this->addModelTrait('IReminders', 'IRemindersImplementation');

        return $this;
    }

    /**
     * Implement category.
     *
     * @return $this
     */
    public function &implementCategory()
    {
        $this->addModelTrait('ICategory', 'ICategoryImplementation');

        return $this;
    }

    /**
     * Implement category.
     *
     * @return $this
     */
    public function &implementCategoriesContext()
    {
        $this->addModelTrait('ICategoriesContext', 'ICategoriesContextImplementation');

        return $this;
    }

    /**
     * Implement category.
     *
     * @return $this
     */
    public function &implementComplete()
    {
        $this->addModelTrait('IComplete', 'ICompleteImplementation');

        return $this;
    }

    /**
     * Implement category.
     *
     * @param  bool  $via_connection_table
     * @return $this
     */
    public function &implementMembers($via_connection_table = false)
    {
        if ($via_connection_table) {
            $this->addModelTrait('IMembers', 'IMembersViaConnectionTableImplementation');
        } else {
            $this->addModelTrait('IMembers', 'IMembersImplementation');
        }

        return $this;
    }

    /**
     * Implement label.
     *
     * @return $this
     */
    public function &implementLabel()
    {
        $this->addModelTrait('ILabel', 'ILabelImplementation');

        return $this;
    }

    /**
     * Implement labels.
     *
     * @return $this
     */
    public function &implementLabels()
    {
        $this->addModelTrait('ILabels', 'ILabelsImplementation');

        return $this;
    }

    /**
     * Implement history.
     *
     * @return $this
     */
    public function &implementHistory()
    {
        $this->addModelTrait('IHistory', 'IHistoryImplementation');

        return $this;
    }

    /**
     * Implement access logs.
     *
     * @return $this
     */
    public function &implementAccessLog()
    {
        $this->addModelTrait('IAccessLog', 'IAccessLogImplementation');

        return $this;
    }

    /**
     * Implement activity log.
     *
     * @return $this
     */
    public function &implementActivityLog()
    {
        $this->addModelTrait('IActivityLog', 'IActivityLogImplementation');

        return $this;
    }

    /**
     * Implement search item.
     *
     * @return $this
     */
    public function &implementSearch()
    {
        $this->addModelTrait(
            '\Angie\Search\SearchItem\SearchItemInterface',
            '\Angie\Search\SearchItem\SearchItemImplementation'
        );

        return $this;
    }

    /**
     * Implement archive interface.
     *
     * @return $this
     */
    public function &implementArchive()
    {
        $this->addModelTrait('IArchive', 'IArchiveImplementation');

        return $this;
    }

    /**
     * Implement trash interface.
     *
     * @return $this
     */
    public function &implementTrash()
    {
        $this->addModelTrait('ITrash', 'ITrashImplementation');

        return $this;
    }

    /**
     * Implement trash interface.
     *
     * @return $this
     */
    public function &implementFavorite()
    {
        $this->addModelTrait('IFavorite', 'IFavoriteImplementation');

        return $this;
    }

    // ---------------------------------------------------
    //  Generation
    // ---------------------------------------------------

    /**
     * Return parent mode instance.
     *
     * @return AngieFrameworkModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Return fields.
     *
     * @return Angie\NamedList
     */
    public function getFields()
    {
        return $this->table->getColumns();
    }

    /**
     * Return destination module name.
     *
     * @return string
     */
    public function getDestinationModuleName()
    {
        return $this->model->getParent() instanceof AngieModule ? $this->model->getParent()->getName() : $this->getInjectInto();
    }

    /**
     * Return inject into module name.
     *
     * @return string
     */
    public function getInjectInto()
    {
        return $this->inject_into;
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Set inject into module name.
     *
     * @param  string                     $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setInjectInto($value)
    {
        $this->inject_into = $value;

        return $this;
    }

    /**
     * Return destination path of the module.
     *
     * @return string
     */
    public function getDestinationModulePath()
    {
        return $this->model->getParent() instanceof AngieModule ? $this->model->getParent()->getPath() : APPLICATION_PATH . '/modules/' . $this->getInjectInto();
    }

    /**
     * Return base_object_extends.
     *
     * @return string
     */
    public function getBaseObjectExtends()
    {
        return $this->base_object_extends;
    }

    /**
     * Set base_object_extends.
     *
     * @param  string                     $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setBaseObjectExtends($value)
    {
        $this->base_object_extends = $value;

        return $this;
    }

    /**
     * Return base_manager_extends.
     *
     * @return string
     */
    public function getBaseManagerExtends()
    {
        return $this->base_manager_extends;
    }

    /**
     * Set base_manager_extends.
     *
     * @param  string                     $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setBaseManagerExtends($value)
    {
        $this->base_manager_extends = $value;

        return $this;
    }

    /**
     * Returns true if object should be abstract class.
     *
     * @return bool
     */
    public function getObjectIsAbstract()
    {
        return $this->object_is_abstract;
    }

    /**
     * Set whether object should be abstract class.
     *
     * @param  bool                       $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setObjectIsAbstract($value)
    {
        $this->object_is_abstract = (bool) $value;

        return $this;
    }

    /**
     * Returns true if manager should be abstract class.
     *
     * @return bool
     */
    public function getManagerIsAbstract()
    {
        return $this->manager_is_abstract;
    }

    /**
     * Set whether manager should be abstract class.
     *
     * @param  bool                       $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setManagerIsAbstract($value)
    {
        $this->manager_is_abstract = (bool) $value;

        return $this;
    }

    /**
     * Return type_from_field.
     *
     * @return string
     */
    public function getTypeFromField()
    {
        return $this->type_from_field;
    }

    /**
     * Set type_from_field.
     *
     * @param  string                     $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setTypeFromField($value)
    {
        $this->type_from_field = $value;

        return $this;
    }

    /**
     * Return generate permissions flag.
     *
     * @return bool
     */
    public function getGeneratePermissions()
    {
        return $this->generate_permissions;
    }

    /**
     * Set generate permissions.
     *
     * @param  bool                       $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setGeneratePermissions($value)
    {
        $this->generate_permissions = (bool) $value;

        return $this;
    }

    /**
     * Return generate URL-s flag.
     *
     * @return bool
     */
    public function getGenerateUrls()
    {
        return $this->generate_urls;
    }

    /**
     * Set generate URL-s flag.
     *
     * @param  bool                       $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setGenerateUrls($value)
    {
        $this->generate_urls = (bool) $value;

        return $this;
    }

    /**
     * Return order_by.
     *
     * @return string
     */
    public function getOrderBy()
    {
        return $this->order_by;
    }

    /**
     * Set order_by.
     *
     * @param  string                     $value
     * @return AngieFrameworkModelBuilder
     */
    public function &setOrderBy($value)
    {
        $this->order_by = $value;

        return $this;
    }

    /**
     * Return list of model associations.
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->associations;
    }
}
