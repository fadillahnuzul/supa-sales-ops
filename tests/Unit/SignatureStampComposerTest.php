<?php

use App\Support\SignatureStampComposer;
use Tests\TestCase;

uses(TestCase::class);

it('places the signature over the stamp in the composed print image', function (): void {
    $signaturePath = tempnam(sys_get_temp_dir(), 'signature-');
    $signature = imagecreatetruecolor(100, 40);
    imagesavealpha($signature, true);
    imagealphablending($signature, false);
    imagefill($signature, 0, 0, imagecolorallocatealpha($signature, 0, 0, 0, 127));
    imagealphablending($signature, true);
    imagefilledrectangle($signature, 0, 18, 99, 22, imagecolorallocate($signature, 0, 0, 0));
    imagepng($signature, $signaturePath);
    imagedestroy($signature);

    $composedPath = null;

    try {
        $composedPath = app(SignatureStampComposer::class)->compose($signaturePath);
        $composedImage = imagecreatefrompng($composedPath);
        $stampImage = imagecreatefrompng(resource_path('images/stempel.png'));

        $centerPixel = imagecolorat($composedImage, 415, 384);
        $stampOverlapPixel = imagecolorat($composedImage, 130, 384);
        $stampRingPixelBeforeComposition = imagecolorat($stampImage, 130, 384);
        $expandedSignaturePixel = imagecolorat($composedImage, 800, 384);
        $stampRingPixel = imagecolorat($stampImage, 415, 120);
        $composedRingPixel = imagecolorat($composedImage, 415, 120);

        expect(imagesx($composedImage))->toBe(831)
            ->and(imagesy($composedImage))->toBe(768)
            ->and($centerPixel & 0xFFFFFF)->toBe(0)
            ->and(($centerPixel >> 24) & 0x7F)->toBe(0)
            ->and($stampOverlapPixel & 0xFFFFFF)->toBe(0)
            ->and($stampRingPixelBeforeComposition & 0xFFFFFF)->not->toBe(0)
            ->and($expandedSignaturePixel & 0xFFFFFF)->toBe(0)
            ->and($composedRingPixel)->toBe($stampRingPixel);

        imagedestroy($composedImage);
        imagedestroy($stampImage);
    } finally {
        if (is_file($signaturePath)) {
            unlink($signaturePath);
        }

        if ($composedPath !== null && is_file($composedPath)) {
            unlink($composedPath);
        }
    }
});
