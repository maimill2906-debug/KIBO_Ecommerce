<?php
/**
 * Helper: SVG Icons & Visual Components for KIBO
 * Chuyển đổi 100% nguyên bản từ Figma Make App (KiboUI.tsx)
 */

function kibo_icon($name, $size = 20) {
    $paths = [
        'paw' => '<path d="M12 13c-2.8 0-5.2 2-5.2 4.4 0 1.6 1.2 2.6 2.8 2.1a8 8 0 0 1 4.8 0c1.6.5 2.8-.5 2.8-2.1C17.2 15 14.8 13 12 13Z" /><circle cx="7.1" cy="10" r="2.1" /><circle cx="16.9" cy="10" r="2.1" /><circle cx="10" cy="5.8" r="2.1" /><circle cx="14.3" cy="5.8" r="2.1" />',
        'search' => '<circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" />',
        'cart' => '<path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H7" /><circle cx="9" cy="20" r="1.5" fill="currentColor" stroke="none" /><circle cx="18" cy="20" r="1.5" fill="currentColor" stroke="none" />',
        'user' => '<circle cx="12" cy="8" r="4" /><path d="M4.5 21a7.5 7.5 0 0 1 15 0" />',
        'phone' => '<path d="M7 3H4.5A1.5 1.5 0 0 0 3 4.5C3 13.6 10.4 21 19.5 21a1.5 1.5 0 0 0 1.5-1.5V17l-4-1-1.2 2a15 15 0 0 1-9.8-9.8L8 7 7 3Z" />',
        'heart' => '<path d="M20.8 5.8a5.5 5.5 0 0 0-7.8 0L12 6.9l-1.1-1.1A5.5 5.5 0 0 0 3.1 13l1.1 1.1L12 22l7.8-7.9 1.1-1.1a5.5 5.5 0 0 0-.1-7.2Z" fill="currentColor" />',
        'heart-outline' => '<path d="M20.8 5.8a5.5 5.5 0 0 0-7.8 0L12 6.9l-1.1-1.1A5.5 5.5 0 0 0 3.1 13l1.1 1.1L12 22l7.8-7.9 1.1-1.1a5.5 5.5 0 0 0-.1-7.2Z" />',
        'truck' => '<path d="M3 5h11v12H3zM14 9h4l3 3v5h-7z" /><circle cx="7" cy="19" r="2" /><circle cx="18" cy="19" r="2" />',
        'shield' => '<path d="M12 3 5 6v5c0 4.6 2.9 8.8 7 10 4.1-1.2 7-5.4 7-10V6l-7-3Z" />',
        'box' => '<path d="m4 8 8-4 8 4-8 4-8-4Z" /><path d="M4 8v9l8 4 8-4V8M12 12v9" />',
        'arrow' => '<path d="m9 18 6-6-6-6" />',
        'close' => '<path d="m6 6 12 12M18 6 6 18" />',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14" />',
        'check' => '<path d="m5 12 4 4L19 6" />',
        'logout' => '<path d="M10 5H4v14h6M14 8l4 4-4 4M8 12h10" />',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />',
        'clock' => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />',
        'bag' => '<path d="M6 8h12l1 13H5L6 8Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" />',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16" />',
        'eye' => '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z" /><circle cx="12" cy="12" r="2.5" />',
        'eyeOff' => '<path d="m3 3 18 18M10.6 6.1A10.7 10.7 0 0 1 12 6c6 0 9.5 6 9.5 6a15 15 0 0 1-2.2 2.9M6.6 6.6A15 15 0 0 0 2.5 12s3.5 6 9.5 6c1.5 0 2.8-.4 4-1" />',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />',
        'ticket' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" /><path d="M13 5v2" /><path d="M13 17v2" /><path d="M13 11v2" />',
    ];

    $path = $paths[$name] ?? '';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/**
 * Logo KIBO chuẩn xác 100% từ Figma KiboUI.tsx
 */
function kibo_logo($inverse = false) {
    $invClass = $inverse ? 'inverse' : '';
    return <<<HTML
<div class="logo {$invClass}">
  <svg class="kibo-mark" viewBox="0 0 52 48" role="img" aria-label="KIBO Pet's Home">
    <path class="kibo-house" d="M9 21.5 26 8l17 13.5V44H9V21.5Z" />
    <path class="kibo-roof" d="M3 19.2 26 1l23 18.2-5 6.1L26 11 8 25.3 3 19.2Z" />
    <path class="kibo-paw" d="M26 28.4c-4.4 0-8 3.3-8 7.1 0 2.5 1.8 4.1 4.3 3.3a12.4 12.4 0 0 1 7.4 0c2.5.8 4.3-.8 4.3-3.3 0-3.8-3.6-7.1-8-7.1Z" />
    <circle class="kibo-paw" cx="18.7" cy="27" r="3.1" />
    <circle class="kibo-paw" cx="33.3" cy="27" r="3.1" />
    <circle class="kibo-paw" cx="22.5" cy="21.8" r="2.8" />
    <circle class="kibo-paw" cx="29.5" cy="21.8" r="2.8" />
  </svg>
  <span class="logo-copy"><strong>KIB<span>O</span></strong><small>PET'S HOME</small></span>
</div>
HTML;
}

function kibo_logo_svg($variant = 'default', $height = 42) {
    $isLight = in_array($variant, ['light', 'white', 'inverse']);
    return kibo_logo($isLight);
}

function kibo_business($key = null) {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../../config/business.php';
    }
    if ($key === null) {
        return $config;
    }
    $parts = explode('.', $key);
    $val = $config;
    foreach ($parts as $p) {
        if (!is_array($val) || !isset($val[$p])) {
            return null;
        }
        $val = $val[$p];
    }
    return $val;
}

function kibo_product_art($type, $color) {
    $typeUpper = strtoupper($type);
    $pawSvg = kibo_icon('paw', 36);
    return <<<HTML
    <div class="product-art" style="--pack: {$color};">
      <div class="pack {$type}">
        <span class="pack-brand">{$typeUpper}</span>
        {$pawSvg}
        <b>PREMIUM<br>PET FOOD</b>
        <small>Complete nutrition</small>
      </div>
      <span class="kibble">••••••</span>
    </div>
HTML;
}
