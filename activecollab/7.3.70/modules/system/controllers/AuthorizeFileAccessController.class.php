<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\AuthorizeFileAccessService\AuthorizeFileAccessServiceInterface;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class AuthorizeFileAccessController extends AuthRequiredController
{
    public function authorize(Request $request, User $user)
    {
        if (!$user instanceof User) {
            return Response::UNAUTHORIZED;
        }

        $parsed_body = $request->getParsedBody();
        foreach (['location', 'intent'] as $required_property) {
            if (!isset($parsed_body[$required_property]) || empty($parsed_body[$required_property])) {
                return Response::BAD_REQUEST;
            }
        }

        if (!in_array($parsed_body['intent'], JwtTokenIssuerInterface::INTENTS)) {
            AngieApplication::log()
                ->error("Trying to authorize with illegal intent '{$parsed_body['intent']}'.");

            return Response::BAD_REQUEST;
        }

        $force = $parsed_body['force'] ?? false;
        $size = $parsed_body['size'] ?? null;
        $scale = $parsed_body['scale'] ?? null;
        $width = null;
        $height = null;

        if ($size && strpos($size, '_')) {
            $size = explode('_', $size);
            $width = $size[0];
            $height = $size[1];
        }

        $search_arguments = [
            'location' => $parsed_body['location'],
        ];

        if (!empty($parsed_body['hash'])) {
            $search_arguments['md5'] = $parsed_body['hash'];
        }

        $file = Attachments::findOneBy(
            $search_arguments
        );

        if (!$file instanceof Attachment) {
            $file = Files::findOneBy(
                $search_arguments
            );
        }

        if (!$file instanceof File && !$file instanceof Attachment) {
            AngieApplication::log()
                ->warning('File not found.', [
                    'location' => $parsed_body['location'],
                    'hash' => !empty($parsed_body['hash']) ? $parsed_body['hash'] : null,
                ]);

            return Response::NOT_FOUND;
        }

        try {
            $redirect_url = AngieApplication::getContainer()
                ->get(AuthorizeFileAccessServiceInterface::class)
                ->authorize(
                    $file,
                    $parsed_body['intent'],
                    $user,
                    $force,
                    $width,
                    $height,
                    $scale
                );
        } catch (LogicException $exception) {
            AngieApplication::log()
                ->error('User does not have permission to access file.', [
                    'user_id' => $user->getId(),
                    'file_id' => $file->getId(),
                    'file_type' => get_class($file),
                ]);

            return Response::NOT_FOUND;
        } catch (Throwable $exception) {
            AngieApplication::log()
                ->error('Failed to authorize user to access file.', [
                    'user_id' => $user->getId(),
                    'file_id' => $file->getId(),
                    'file_type' => get_class($file),
                ]);

            return Response::NOT_FOUND;
        }

        AngieApplication::log()
            ->info('User authorized for file access', [
                'user_id' => $user->getId(),
                'file_id' => $file->getId(),
                'file_type' => get_class($file),
            ]);

        return [
            'is_ok' => true,
            'redirect_url' => $redirect_url,
        ];
    }
}
