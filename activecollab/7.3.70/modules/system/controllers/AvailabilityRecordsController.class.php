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

class AvailabilityRecordsController extends AuthRequiredController
{
    public function index(Request $request, User $user)
    {
        /** @var User $for_user */
        $for_user = Users::findById($request->getId('user_id'));

        if (!$for_user || $for_user->getIsTrashed()) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => lang('Cannot fetch availabilities for this user.')]
            );
        }

        return AvailabilityRecords::prepareCollection(
            sprintf(
                'availability_records_for_user_%s_scope_%s_from_%s_to_%s',
                $for_user->getId(),
                $request->get('scope', 'all'),
                $request->get('from', ''),
                $request->get('to', '')
            ),
            $user
        );
    }

    public function add(Request $request, User $user)
    {
        if (!AvailabilityRecords::canAdd($user)) {
            return Response::FORBIDDEN;
        }

        $for_user = Users::findById($request->getId('user_id'));

        if (!$for_user instanceof User) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => lang('Cannot add availability for non-existing user.')]
            );
        }

        try {
            return AvailabilityRecords::createAvailability($user, $for_user, $request->post() ?? []);
        } catch (LogicException $e) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => $e->getMessage()]
            );
        }  catch (InsufficientPermissionsError $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        } catch (ValidationErrors $e) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => $e->getErrorsAsString()]
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error while adding availability record.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => lang('Failed to create availability. Please contact our support.')]
            );
        }
    }

    public function delete(Request $request, User $user)
    {
        /** @var AvailabilityRecord $availability_record */
        $availability_record = AvailabilityRecords::findById(
            $request->getId('availability_record_id')
        );

        if (!$availability_record) {
            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('Availability record not found.')]
            );
        }

        try {
            return AvailabilityRecords::removeAvailability($availability_record, $user);
        } catch (InsufficientPermissionsError $e) {
            return new StatusResponse(
                Response::FORBIDDEN,
                '',
                ['message' => $e->getMessage()]
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error while deleting availability record.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => lang('Failed to delete availability. Please contact our support.')]
            );
        }
    }

    public function all(Request $request, User $user)
    {
      $today = (new DateValue())->toMySQL();
      $start_date = new DateValue($request->get('start_date', $today));
      $end_date = new DateValue($request->get('end_date', $today));

      if ($start_date > $end_date) {
        return new StatusResponse(
            Response::BAD_REQUEST,
            '',
            ['message' => lang('Start date must be before end date.')]
        );
      }

      return AvailabilityRecords::prepareCollection(
          sprintf(
              'all_availability_records_from_%s_to_%s',
              $start_date->toMySQL(),
              $end_date->toMySQL()
          ),
          $user
      );
    }
}
