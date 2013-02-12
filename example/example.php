<?php

// This is an example of printing a sentence of Korean using the open-source Nanum Brush font.

$imgtext = new IMGText;
$imgtext->cache_url_prefix = '.';
$imgtext->cache_local_dir = dirname(__FILE__);
$imgtext->font_dir = '/usr/share/fonts/truetype/nanum';

echo $imgtext->get_html("네이버 '나눔손글씨 붓'으로 작성된 제목", 'nanumbrush', 24, '666', false, $height);
