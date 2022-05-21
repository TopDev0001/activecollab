<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('project', SystemModule::NAME);

class AppliedTemplatesController extends ProjectController
{
    public function applied_templates(): array
    {
        return $this->active_project->getAppliedTemplateIds();
    }

    public function apply_template(Request $request, User $user)
    {
        if (!$this->active_project->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        $template = $this->getProjectTemplate($request);
        if (!$template) {
            return Response::BAD_REQUEST;
        }

        try {
            return $this->active_project->applyTemplate(
                $template,
                $user,
                false,
                $this->getTemplateDateReference($request),
            );
        } catch (Throwable $e) {
            return $e;
        }
    }

    private function getProjectTemplate(Request $request): ?ProjectTemplate
    {
        $template_id = (int) $request->post('template_id');
        if ($template_id < 1) {
            return null;
        }

        $template = AngieApplication::getContainer()
            ->get(DataObjectPoolInterface::class)
                ->get(ProjectTemplate::class, $template_id);

        if ($template instanceof ProjectTemplate) {
            return $template;
        }

        return null;
    }

    private function getTemplateDateReference(Request $request): DateValue
    {
        $reference = (string) $request->post('template_date_reference');

        if ($reference) {
            return DateValue::makeFromString($reference);
        }

        return DateValue::now();
    }
}
