<?php

declare(strict_types=1);

namespace KadrPortal\Helpers;

use RuntimeException;

/**
 * Create a thumbnail with a center crop preserving the aspect ratio.
 */
function create_thumbnail(string $sourcePath, string $destinationPath, int $width, int $height): void
{
    $imageInfo = getimagesize($sourcePath);

    if ($imageInfo === false) {
        throw new RuntimeException('Не удалось получить информацию об изображении.');
    }

    [$sourceWidth, $sourceHeight] = $imageInfo;
    $mime = $imageInfo['mime'] ?? '';

    $sourceImage = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png' => imagecreatefrompng($sourcePath),
        default => null,
    };

    if ($sourceImage === null) {
        throw new RuntimeException('Поддерживаются только JPG и PNG файлы.');
    }

    $sourceRatio = $sourceWidth / $sourceHeight;
    $targetRatio = $width / $height;

    if ($sourceRatio > $targetRatio) {
        $scaledHeight = $height;
        $scaledWidth = (int) round($height * $sourceRatio);
    } else {
        $scaledWidth = $width;
        $scaledHeight = (int) round($width / $sourceRatio);
    }

    $temporaryImage = imagecreatetruecolor($scaledWidth, $scaledHeight);

    if ($temporaryImage === false) {
        imagedestroy($sourceImage);
        throw new RuntimeException('Не удалось обработать изображение.');
    }

    if ($mime === 'image/png') {
        imagealphablending($temporaryImage, false);
        imagesavealpha($temporaryImage, true);
    }

    if (!imagecopyresampled(
        $temporaryImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $scaledWidth,
        $scaledHeight,
        $sourceWidth,
        $sourceHeight
    )) {
        imagedestroy($sourceImage);
        imagedestroy($temporaryImage);
        throw new RuntimeException('Не удалось изменить размер изображения.');
    }

    $thumbnail = imagecreatetruecolor($width, $height);

    if ($thumbnail === false) {
        imagedestroy($sourceImage);
        imagedestroy($temporaryImage);
        throw new RuntimeException('Не удалось создать превью.');
    }

    if ($mime === 'image/png') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }

    $offsetX = (int) max(0, floor(($scaledWidth - $width) / 2));
    $offsetY = (int) max(0, floor(($scaledHeight - $height) / 2));

    if (!imagecopy(
        $thumbnail,
        $temporaryImage,
        0,
        0,
        $offsetX,
        $offsetY,
        $width,
        $height
    )) {
        imagedestroy($sourceImage);
        imagedestroy($temporaryImage);
        imagedestroy($thumbnail);
        throw new RuntimeException('Не удалось обрезать превью.');
    }

    $result = $mime === 'image/png'
        ? imagepng($thumbnail, $destinationPath, 6)
        : imagejpeg($thumbnail, $destinationPath, 85);

    imagedestroy($sourceImage);
    imagedestroy($temporaryImage);
    imagedestroy($thumbnail);

    if ($result === false) {
        throw new RuntimeException('Не удалось сохранить превью.');
    }
}
