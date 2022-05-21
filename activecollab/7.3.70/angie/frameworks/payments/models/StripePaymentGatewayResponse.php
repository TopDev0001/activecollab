<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class StripePaymentGatewayResponse extends PaymentGatewayResponse
{
    private bool $is_3d_secure;
    private ?string $redirect_url;

    public function __construct(
        float $amount,
        string $transaction_id,
        bool $is_3d_secure = false,
        ?string $redirect_url = null,
        ?DateTimeValue $paid_on = null,
        ?string $token = null
    ) {
        parent::__construct(
            $amount,
            $transaction_id,
            $paid_on,
            $token
        );
        $this->is_3d_secure = $is_3d_secure;
        $this->redirect_url = $redirect_url;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirect_url;
    }

    public function setRedirectUrl(?string $redirect_url): void
    {
        $this->redirect_url = $redirect_url;
    }

    public function is3dSecure(): bool
    {
        return $this->is_3d_secure;
    }

    public function setIs3dSecure(bool $is_3d_secure): void
    {
        $this->is_3d_secure = $is_3d_secure;
    }
}
