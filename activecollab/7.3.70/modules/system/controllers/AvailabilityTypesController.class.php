<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class AvailabilityTypesController extends AuthRequiredController
{
    /**
     * @var AvailabilityType
     */
    private $availability_type;

    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->availability_type = AvailabilityTypes::findById(
            $request->getId('availability_type_id')
        );
    }

    public function index(Request $request, User $user)
    {
         return AvailabilityTypes::prepareCollection('availability_types', $user);
    }

    public function add(Request $request, User $user)
    {
        return AvailabilityTypes::canAdd($user)
            ? AvailabilityTypes::create($request->post())
            : Response::NOT_FOUND;
    }

    public function edit(Request $request, User $user)
    {
        return $this->availability_type && $this->availability_type->canEdit($user)
            ? AvailabilityTypes::update($this->availability_type, $request->put())
            : Response::NOT_FOUND;
    }

    public function view(Request $request, User $user)
    {
        return $this->availability_type && $this->availability_type->canView($user)
            ? $this->availability_type
            : Response::NOT_FOUND;
    }

    public function delete(Request $request, User $user)
    {
        if ($this->availability_type && $this->availability_type->canDelete($user)) {
            try {
                return AvailabilityTypes::scrap($this->availability_type);
            } catch (Exception $e) {
                return new StatusResponse(
                    Response::FORBIDDEN,
                    '',
                    ['message' => $e->getMessage()]
                );
            }
        }

        return Response::NOT_FOUND;
    }
}
