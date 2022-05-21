<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Modules;

use Angie\Events;
use Angie\Inflector;
use Angie\ModuleModelFactory;
use AngieApplication;
use AngieFrameworkModel;
use Closure;
use FileDnxError;
use ReflectionClass;

abstract class AngieFramework implements AngieFrameworkInterface
{
    const INJECT_INTO = 'system';

    protected string $name = '';
    protected string $version = '1.0';

    public function init()
    {
        $this->defineClasses();
    }

    public function defineClasses()
    {
    }

    public function defineHandlers()
    {
    }

    public function defineListeners(): array
    {
        return [];
    }

    /**
     * Subscribe $callback function to $event.
     *
     * @param string         $event
     * @param Closure|string $callback
     */
    protected function listen($event, $callback = null)
    {
        if (empty($callback)) {
            $callback = "$this->name/$event";
        } else {
            if (is_string($callback) && strpos($callback, '/') === false) {
                $callback = "$this->name/$callback";
            }
        }

        Events::listen($event, $callback);
    }

    // ---------------------------------------------------
    //  Paths
    // ---------------------------------------------------

    /**
     * Return framework name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPath(): string
    {
        return ANGIE_PATH . '/frameworks/' . $this->name;
    }

    // ---------------------------------------------------
    //  Model and installation
    // ---------------------------------------------------

    private bool $model_loaded = false;
    private ?AngieFrameworkModel $model = null;

    public function getModel(): ?AngieFrameworkModel
    {
        if (!$this->model_loaded) {
            $this->model = (new ModuleModelFactory())->createModuleModel($this);
            $this->model_loaded = true;
        }

        return $this->model;
    }

    private ?string $module_class_name = null;
    private ?string $namespace = null;

    public function getModuleClassName(): string
    {
        if ($this->module_class_name === null) {
            $this->getNamespaceAndClassName();
        }

        return $this->module_class_name;
    }

    public function getNamespace(): string
    {
        if ($this->namespace === null) {
            $this->getNamespaceAndClassName();
        }

        return $this->namespace;
    }

    public function getNamespacedModuleClass(string ...$subspaces): string
    {
        return sprintf('%s\\%s', $this->getNamespace(), implode('\\', $subspaces));
    }

    private function getNamespaceAndClassName(): void
    {
        $reflection_class = new ReflectionClass($this);

        $class_name_bits = explode('\\', $reflection_class->getName());

        $this->module_class_name = $class_name_bits[count($class_name_bits) - 1];
        $this->namespace = $reflection_class->getNamespaceName();
    }

    public function install()
    {
        if ($this->getModel() instanceof AngieFrameworkModel) {
            $this->getModel()->createTables();
            $this->getModel()->loadInitialData();
        }
    }

    // ---------------------------------------------------
    //  Path resolution and loading
    // ---------------------------------------------------

    /**
     * Load controller class.
     *
     * @param  string $controller_name
     * @return string
     */
    public function useController($controller_name)
    {
        $controller_class = Inflector::camelize($controller_name) . 'Controller';
        if (!class_exists($controller_class, false)) {
            $controller_file = $this->getPath() . "/controllers/$controller_class.class.php";

            if (is_file($controller_file)) {
                include_once $controller_file;
            } else {
                throw new FileDnxError($controller_file, "Controller $this->name::$controller_name does not exist (expected location '$controller_file')");
            }
        }

        return $controller_class;
    }

    /**
     * Use specific helper.
     *
     * @param  string $helper_name
     * @param  string $helper_type
     * @return string
     */
    public function useHelper($helper_name, $helper_type = 'function')
    {
        if (!function_exists("smarty_{$helper_type}_{$helper_name}")) {
            $helper_file = $this->getPath() . "/helpers/$helper_type.$helper_name.php";

            if (is_file($helper_file)) {
                include_once $helper_file;
            } else {
                throw new FileDnxError($helper_file, "Helper $this->name::$helper_name does not exist (expected location '$helper_file')");
            }
        }

        return "smarty_{$helper_type}_{$helper_name}";
    }

    /**
     * Use specific model.
     *
     * $model_names can be single model name or array of model names
     *
     * @param string|string[] $model_names
     */
    public function useModel($model_names)
    {
        foreach ((array) $model_names as $model_name) {
            $object_class = Inflector::camelize(Inflector::singularize($model_name));
            $manager_class = Inflector::camelize($model_name);

            AngieApplication::setForAutoload(
                [
                    "Base$object_class" => $this->getPath() . "/models/$model_name/Base$object_class.class.php",
                    $object_class => $this->getPath() . "/models/$model_name/$object_class.class.php",
                    "Base$manager_class" => $this->getPath() . "/models/$model_name/Base$manager_class.class.php",
                    $manager_class => $this->getPath() . "/models/$model_name/$manager_class.class.php",
                ]
            );
        }
    }

    /**
     * Use specific model.
     *
     * $model_names can be single model name or array of model names
     *
     * @param string|string[] $view_names
     */
    public function useView($view_names)
    {
        foreach ((array) $view_names as $view_name) {
            $view_class = Inflector::camelize($view_name);

            AngieApplication::setForAutoload(
                [
                    "Base{$view_class}" => $this->getPath() . "/models/$view_name/Base{$view_class}.class.php",
                    $view_class => $this->getPath() . "/models/$view_name/$view_class.class.php",
                ]
            );
        }
    }

    public function getEmailTemplatePath(string $template): string
    {
        return $this->getPath() . "/email/$template.tpl";
    }

    public function getEventHandlerPath(string $callback_name): string
    {
        return $this->getPath() . "/handlers/$callback_name.php";
    }
}
