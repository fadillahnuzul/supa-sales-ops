<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

class SignatureStampComposer
{
    public function compose(?string $signaturePath): string
    {
        $stampPath = resource_path('images/stempel.png');
        $stamp = imagecreatefrompng($stampPath);

        if ($stamp === false) {
            throw new RuntimeException('Tidak dapat membaca gambar stempel.');
        }

        $canvas = imagecreatetruecolor(imagesx($stamp), imagesy($stamp));

        if ($canvas === false) {
            imagedestroy($stamp);

            throw new RuntimeException('Tidak dapat membuat gambar TTD dengan stempel.');
        }

        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopy($canvas, $stamp, 0, 0, 0, 0, imagesx($stamp), imagesy($stamp));
        imagedestroy($stamp);

        if ($signaturePath !== null) {
            $signature = $this->loadSignature($signaturePath);
            $signatureSize = getimagesize($signaturePath);

            if ($signatureSize === false) {
                imagedestroy($signature);
                imagedestroy($canvas);

                throw new RuntimeException('Tidak dapat membaca gambar TTD.');
            }

            $scale = min(
                imagesx($canvas) * 0.95 / imagesx($signature),
                imagesy($canvas) * 0.65 / imagesy($signature)
            );
            $signatureWidth = max(1, (int) round(imagesx($signature) * $scale));
            $signatureHeight = max(1, (int) round(imagesy($signature) * $scale));
            $signatureLayer = imagecreatetruecolor($signatureWidth, $signatureHeight);

            if ($signatureLayer === false) {
                imagedestroy($signature);
                imagedestroy($canvas);

                throw new RuntimeException('Tidak dapat membuat lapisan gambar TTD.');
            }

            imagesavealpha($signatureLayer, true);
            imagealphablending($signatureLayer, false);
            imagefill(
                $signatureLayer,
                0,
                0,
                imagecolorallocatealpha($signatureLayer, 0, 0, 0, 127)
            );
            imagecopyresampled(
                $signatureLayer,
                $signature,
                0,
                0,
                0,
                0,
                $signatureWidth,
                $signatureHeight,
                imagesx($signature),
                imagesy($signature)
            );
            imagedestroy($signature);

            if ($signatureSize[2] === IMAGETYPE_JPEG) {
                $this->makeWhiteBackgroundTransparent($signatureLayer);
            }

            imagealphablending($canvas, true);
            imagecopy(
                $canvas,
                $signatureLayer,
                (int) floor((imagesx($canvas) - $signatureWidth) / 2),
                (int) floor((imagesy($canvas) - $signatureHeight) / 2),
                0,
                0,
                $signatureWidth,
                $signatureHeight
            );
            imagedestroy($signatureLayer);
        }

        $tempDirectory = storage_path('app/temp');

        if (! is_dir($tempDirectory) && ! mkdir($tempDirectory, 0755, true) && ! is_dir($tempDirectory)) {
            imagedestroy($canvas);

            throw new RuntimeException('Tidak dapat membuat direktori gambar TTD sementara.');
        }

        $outputPath = $tempDirectory.DIRECTORY_SEPARATOR.'signature-stamp-'.Str::uuid().'.png';
        $saved = imagepng($canvas, $outputPath);
        imagedestroy($canvas);

        if (! $saved) {
            throw new RuntimeException('Tidak dapat menyimpan gambar TTD dengan stempel.');
        }

        return $outputPath;
    }

    private function loadSignature(string $signaturePath): \GdImage
    {
        $imageSize = getimagesize($signaturePath);

        if ($imageSize === false) {
            throw new RuntimeException('File TTD bukan gambar yang valid.');
        }

        $image = match ($imageSize[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($signaturePath),
            IMAGETYPE_JPEG => imagecreatefromjpeg($signaturePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($signaturePath),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Format gambar TTD tidak dapat dibaca.');
        }

        return $image;
    }

    private function makeWhiteBackgroundTransparent(\GdImage $image): void
    {
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $color = imagecolorat($image, $x, $y);
                $red = ($color >> 16) & 0xFF;
                $green = ($color >> 8) & 0xFF;
                $blue = $color & 0xFF;
                $whiteness = min($red, $green, $blue);

                if ($whiteness <= 225) {
                    continue;
                }

                $alpha = min(127, (int) round(($whiteness - 225) * (127 / 30)));
                imagesetpixel(
                    $image,
                    $x,
                    $y,
                    imagecolorallocatealpha($image, $red, $green, $blue, $alpha)
                );
            }
        }
    }
}
