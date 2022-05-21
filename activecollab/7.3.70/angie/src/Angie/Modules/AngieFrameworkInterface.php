<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Modules;

use AngieFrameworkModel;

interface AngieFrameworkInterface
{
    public function init();
    public function defineClasses();
    public function defineHandlers();
    public function defineListeners(): array;

    /**
     * Return framework name.
     *
     * @return string
     */
    public function getName();

    public function getVersion(): string;
    public function getPath(): string;
    public function getModel(): ?AngieFrameworkModel;
    public function getModuleClassName(): string;
    public function getNamespace(): string;
    public function getNamespacedModuleClass(string ...$subspaces): string;

    /**
     * Install this framework.
     */
    public function install();

    /**
     * Load controller class.
     *
     * @param  string $controller_name
     * @return string
     */
    public function useController($controller_name);

    /**
     * Use specific helper.
     *
     * @param  string $helper_name
     * @param  string $helper_type
     * @return string
     */
    public function useHelper($helper_name, $helper_type = 'function');

    /**
     * Use specific model.
     *
     * $model_names can be single model name or array of model names
     *
     * @param string|string[] $model_names
     */
    public function useModel($model_names);

    /**
     * Use specific model.
     *
     * $model_names can be single model name or array of model names
     *
     * @param string|string[] $view_names
     */
    public function useView($view_names);
    public function getEmailTemplatePath(string $template): string;
    public function getEventHandlerPath(string $callback_name): string;
}
