<?php
$sourcePath = 'C:/Users/VELORA/.gemini/antigravity/brain/0431b922-e29d-4418-846e-6dc1619da9ea/.user_uploaded/media_1793363506084_e5875ad3.png';
$src = imagecreatefrompng($sourcePath);
$width = imagesx($src);
$height = imagesy($src);

// Cắt vùng hero banner: từ y = 140 đến y = 608 (bỏ thanh taskbar windows bên dưới)
$heroHeight = 608 - 140;
$dest = imagecreatetruecolor($width, $heroHeight);
imagecopy($dest, $src, 0, 0, 0, 140, $width, $heroHeight);

$outPath = 'C:/xampp/htdocs/kibo/assets/images/hero_banner_figma.png';
imagepng($dest, $outPath, 9);
imagedestroy($dest);
imagedestroy($src);

echo "Hero banner cropped successfully: " . $width . "x" . $heroHeight . "\n";
