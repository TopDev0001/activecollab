<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Controller\Controller;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;
use Angie\Http\Response\StatusResponse\StatusResponseInterface;

/**
 * Framework level warehouse controller.
 *
 * @package angie.frameworks.attachments
 * @subpackage controllers
 */
abstract class FwWarehouseController extends Controller
{
    /**
     * Respond on pingback request.
     *
     * @return StatusResponseInterface|int
     */
    public function pingback(Request $request)
    {
        $post = $request->post();
        $intent = isset($post['intent']) ? $post['intent'] : null;

        /** @var WarehouseIntegration $warehouse_integration */
        $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);

        if ($warehouse_integration->isUploadIntentValid($intent)) {
            return new StatusResponse(Response::ACCEPTED, '', $warehouse_integration->onPingbackRequest($post));
        }

        return Response::NOT_ACCEPTABLE;
    }

    /**
     * Handle pingback request upon Warehouse completes store export.
     */
    public function store_export_pingback(Request $request)
    {
        if (($signature = $request->post('signature')) && ($export_memory = AngieApplication::memories()->get('export_' . $signature))) {
            $user = Users::findByEmail($export_memory['email'], true);
            $recipient = $user instanceof User ? $user : new AnonymousUser(null, $export_memory['email']);
            $download_url = $request->post('download_url');
            $archive_size = $request->post('size');

            /** @var WarehouseIntegration $warehouse_integration */
            $warehouse_integration = Integrations::findFirstByType(WarehouseIntegration::class);
            $warehouse_integration->onStoreExportPingback($recipient, $export_memory, $download_url, $archive_size);

            AngieApplication::memories()->forget('export_' . $signature);
        } else {
            AngieApplication::log()->info('Signature missing for store export pingback request');
        }
    }
}
