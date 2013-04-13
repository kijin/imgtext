<?php

// This is an example of printing a sentence of Korean using the open-source Nanum Brush font.

include '../imgtext.php';
$imgtext = new IMGText;
$imgtext->cache_url_prefix = '.';
$imgtext->cache_local_dir = dirname(__FILE__);

// Font settings.

$imgtext->font_dir = '/usr/share/fonts/truetype/nanum';
$imgtext->font_name = 'NanumBrush';
$imgtext->font_size = 32;
$imgtext->color = '#404040';

// Shadow settings.

$imgtext->shadow = true;
$imgtext->shadow_offset = array(2, 2);      // Horizontal, vertical.
$imgtext->shadow_opacity = 64;              // 0 = Opaque, 127 = Transparent.
$imgtext->shadow_color = '#a0a0a0';
$imgtext->shadow_blur = 2;

// Padding settings. Use this if your font gets cut at the edge.

$imgtext->padding = array(0, 0, 0, 0);      // CSS-style: top-right-bottom-left.

// Generate the HTML.

echo $imgtext->get_html("네이버 ‘나눔손글씨 붓’으로 작성된 제목");
