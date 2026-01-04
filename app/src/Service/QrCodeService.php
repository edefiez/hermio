<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function generateQrCode(string $data, int $size = 300): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
            labelText: '',
            labelFont: new OpenSans(16),
            labelAlignment: LabelAlignment::Center,
            labelMargin: new Margin(0, 0, 0, 0),
            labelTextColor: new Color(0, 0, 0),
            logoPath: '',
            logoResizeToWidth: 0,
            logoResizeToHeight: 0,
            logoPunchoutBackground: false
        );

        $result = $builder->build();

        return $result->getString();
    }

    public function generateQrCodeBase64(string $data, int $size = 300): string
    {
        $qrCodeData = $this->generateQrCode($data, $size);
        return 'data:image/png;base64,' . base64_encode($qrCodeData);
    }
}

