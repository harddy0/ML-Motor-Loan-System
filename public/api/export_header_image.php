<?php
require_once __DIR__ . '/../../src/includes/init.php';

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!function_exists('imagecreatetruecolor')) {
    http_response_code(500);
    exit;
}

$text = strtoupper(trim((string)($_GET['text'] ?? 'ML MOTORCYCLE LOAN')));
if ($text === '') {
    $text = 'ML MOTORCYCLE LOAN';
}

$width = 1400;
$height = 140;
$img = imagecreatetruecolor($width, $height);

$white = imagecolorallocate($img, 255, 255, 255);
$line = imagecolorallocate($img, 203, 213, 225);
$textColor = imagecolorallocate($img, 100, 116, 139);
imagefill($img, 0, 0, $white);

$diamondPath = __DIR__ . '/../assets/img/ml-diamond.png';
$logoPath = __DIR__ . '/../assets/img/ml-logo-1.png';

$drawPng = static function ($canvas, $path, $dstX, $dstY, $targetH): void {
    if (!is_file($path)) {
        return;
    }

    $src = @imagecreatefrompng($path);
    if (!$src) {
        return;
    }

    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($srcW <= 0 || $srcH <= 0) {
        imagedestroy($src);
        return;
    }

    $dstW = max(1, (int)round(($srcW / $srcH) * $targetH));
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);
    imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $dstW, $targetH, $srcW, $srcH);
    imagedestroy($src);
};

$drawPng($img, $diamondPath, 26, 38, 64);

if (is_file($logoPath)) {
    $src = @imagecreatefrompng($logoPath);
    if ($src) {
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW > 0 && $srcH > 0) {
            $targetH = 58;
            $targetW = max(1, (int)round(($srcW / $srcH) * $targetH));
            $dstX = (int)round(($width - $targetW) / 2);
            $dstY = 24;
            imagealphablending($img, true);
            imagesavealpha($img, true);
            imagecopyresampled($img, $src, $dstX, $dstY, 0, 0, $targetW, $targetH, $srcW, $srcH);
        }
        imagedestroy($src);
    }
}

$font = 5;
$textW = imagefontwidth($font) * strlen($text);
$textX = (int)max(8, ($width - $textW) / 2);
$textY = 96;
imagestring($img, $font, $textX, $textY, $text, $textColor);

imageline($img, 0, $height - 1, $width, $height - 1, $line);

imagepng($img);
imagedestroy($img);
