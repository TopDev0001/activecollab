<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Signer;

trait SignerResolver
{
    private function resolveSigner(?string $signer = '')
    {
        if (empty($signer)) {
            $signer = self::DEFAULT_SIGNER;
        }

        if (!in_array($signer, array_keys(self::SIGNERS))) {
            throw new \InvalidArgumentException("Provided signer '{$signer}' does not exist");
        }

        $signer_class = self::SIGNERS[$signer];

        return new $signer_class;
    }
}
