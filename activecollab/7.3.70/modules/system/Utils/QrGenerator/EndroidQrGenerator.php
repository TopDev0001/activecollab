<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\QrGenerator;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Factory\QrCodeFactory;
use Endroid\QrCode\QrCode;

class EndroidQrGenerator implements QrGeneratorInterface
{
    private $qr_code_factory;
    private $data;

    public function __construct(QrCodeFactory $qr_code_factory)
    {
        $this->qr_code_factory = $qr_code_factory;
    }

    public function generate(string $data): QrGeneratorInterface
    {
        $this->data = new QrCode($data);
        $this->data->setSize(500);
        $this->data->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());

        return $this;
    }

    public function writeString(): string
    {
        return $this->data->writeString();
    }

    public function writeDataUri(): string
    {
        return $this->data->writeDataUri();
    }

    public function writeFile(string $path): void
    {
        $this->data->writeFile($path);
    }
}
