<?php
$src = 'C:/Users/VELORA/.gemini/antigravity/brain/0431b922-e29d-4418-846e-6dc1619da9ea/.user_uploaded/media_1793363506084_e5875ad3.png';
$dest = 'C:/xampp/htdocs/kibo/assets/images/hero_exact.png';
if (copy($src, $dest)) {
    echo "COPIED_OK: " . filesize($dest) . " bytes\n";
} else {
    echo "FAILED_COPY\n";
}
