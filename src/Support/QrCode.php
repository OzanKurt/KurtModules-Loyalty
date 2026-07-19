<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class QrCode
{
    /**
     * Render a value as an inline SVG QR code (no XML prolog, safe to embed).
     */
    public static function svg(string $value, int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        $svg = $writer->writeString($value);

        $start = strpos($svg, '<svg');

        return $start === false ? $svg : substr($svg, $start);
    }
}
