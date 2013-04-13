
Introduction
------------

IMGText is a PHP library that generates a PNG image for each word in a string,
and then generates HTML markup to display those images as if they were text.

[This documentation is also available in Korean.](./README.KO.md)

### Example Code:

    $imgtext = new IMGText;
    $imgtext->cache_url_prefix = '.';
    $imgtext->cache_local_dir = dirname(__FILE__);
    $imgtext->font_dir = '/usr/share/fonts/truetype/nanum';
    $imgtext->font_name = 'NanumBrush';
    $imgtext->font_size = 32;
    $imgtext->color = '#404040';
    $imgtext->shadow = true;
    $imgtext->shadow_blur = 2;
    echo $imgtext->get_html("네이버 ‘나눔손글씨 붓’으로 작성된 제목");

### Example Output:

> <img class="imgtext" src="https://github.com/kijin/imgtext/raw/master/example/imgtext.4b470a0626e7ca.word-001.png" alt="네이버" title="" />&nbsp;
  <img class="imgtext" src="https://github.com/kijin/imgtext/raw/master/example/imgtext.4b470a0626e7ca.word-002.png" alt="‘나눔손글씨" title="" />&nbsp;
  <img class="imgtext" src="https://github.com/kijin/imgtext/raw/master/example/imgtext.4b470a0626e7ca.word-003.png" alt="붓’으로" title="" />&nbsp;
  <img class="imgtext" src="https://github.com/kijin/imgtext/raw/master/example/imgtext.4b470a0626e7ca.word-004.png" alt="작성된" title="" />&nbsp;
  <img class="imgtext" src="https://github.com/kijin/imgtext/raw/master/example/imgtext.4b470a0626e7ca.word-005.png" alt="제목" title="" />&nbsp;

### Why Use IMGText?

Most of the time, Western text on the Web can be easily stylized with web fonts.
Since the majority of Western writing systems contain only a few dozen glyphs,
web fonts tend to be small -- a few dozen KB -- and thus quick to download.
This is unfortunately not the case for a lot of Asian scripts, such as Korean,
Japanese, and Chinese (both traditional and simplified). These languages
contain thousands of glyphs, resulting in web fonts that are several MB in size.

As a result, many Asian web designers are stuck with OS default fonts,
or resort to images. (Other options, such as <canvas>, is rarely used because
the affected countries also have a major infestation of outdated browsers.)
The former looks hideous, especially at large font sizes used in headings.
The latter, on the other hand, suffers from several disadvantages:

  - Each image needs to be created by hand, and it is difficult to maintain
    consistency in font size, color, etc. over a long time.
  - Search engines and screen readers cannot access the original text,
    unless each image is accompanied by corresponding alt-text,
    which again needs to be created by hand.
  - Text can be easily re-flowed when the screen is resized,
    but images can only be zoomed, leading to broken layout and/or
    poor legitibility in mobile devices.

IMGText is meant to solve these problems, not by addressing the source of
the problems (lack of usable web fonts for Asian scripts), but by acknowledging
the sad reality and automating image generation and use as much as possible.

  - Given a string, a font, and some options such as font size and color,
    IMGText generates a PNG image for each word in the string,
    and also generates HTML markup to display them as if they were text.
    You only need to take the HTML markup and place it in your pages.
  - Since each word is turned into a separate image, the result can be
    easily re-flowed just like plain text.
    (This only works with texts that contain spaces between words.
    IMGText is optimized for Korean. Texts written in other languages might not
    work as well if they do not contain spaces.)
  - You can add shadows and adjust their location, color, and transparency.
  - Additional styling can be applied by using CSS and/or JavaScript
    on `<img>` tags with the `imgtext` class.
  - Each image is accompanied by corresponding alt-text, making the result
    readable by search engines and screen readers.
  - The annoying hover effect in Internet Explorer is also taken care of.
  - Images and HTML markup are cached and reused every time you request
    an identical string with identical styles. This makes IMGText very fast.

### API Reference

To use IMGText, include the `imgtext.php` file into your project.
You will also need:

  - At least one TTF font file that contains all the glyphs you need.
  - A cache directory that is writable by PHP.
    This is where generated PNG images and metadate will be stored.
    This directory should also be accessible by visitors to your web site.

Please see the example above for an overview of how IMGText works.
Here is a list of available methods, properties, and their signatures:

#### Properties

  - _string_ **cache_url_prefix** (required):
    The web-accessible path of the cache directory.
    Generated HTML markup will contain references to this directory.
    Do not include a trailing slash.
    Example: `./images/cache`

  - _string_ **cache_local_dir** (required):
    The local path that corresponds to the same cache directory.
    Generated images and metadate will be stored in this directory.
    Do not include a trailing slash.
    Example: `/srv/www/website_name/htdocs/images/cache`

  - _string_ **font_dir** (required):
    The local path where fonts are stored.
    Do not include a trailing slash.
    Example: `/usr/share/fonts`.
  
  - _string_ **font_ext** (optional):
    The extension of your font files. The default value is `ttf`.
    Change this if you want to use OTF fonts.
    This value is case-sensitive.

  - _string_ **font_name** (required):
    The name of the font file that you want to use.
    This value is case-sensitive.
    
  - _int_ **$font_size** (optional):
    The size of the font that you want to use, measured in points.
    The default value is 32 points.
    
  - _int_ **$image_height** (optional):
    The height of the generated images, in pixels.
    You can use this argument to force a specific height.
    By default, the height is automatically determined.
    
  - _hex_ **$color** (optional):
    The color of the text, in a hexademical notation similar to CSS,
    such as `#0000ff` (blue).
    Three-digit shortcuts are also supported, such as `#f00` (red).
    The default value is `#000000` (black).
    
  - _hex_ **$background_color** (optional):
    The background color. The syntax is the same as $color above.
    Use `false` to indicate a transparent background.
    The default value is transparent.
    
  - _array_ **$padding** (optional):
    The padding around each image, measured in pixels.
    Your array should contain 4 integers. The order is the same as in CSS,
    i.e. top-right-bottom-left. Padding may be useful if your font
    contains glyphs with large swashes that extend outside of images
    generated by IMGText. Use CSS with negative margins to cancel out
    the extra padding in that case.
    The default value is no padding.

  - _bool_ **$shadow** (optional):
    Change this to `true` to enable text shadows.
    The default value is `false`.
    
  - _hex_ **$shadow_color** (optional):
    The color of text shadows. The syntax is the same as $color above.
    The default value is `#000000` (black).
    
  - _int_ **$shadow_opacity** (optional):
    The opacity of text shadows.
    This should be an integer between 0 and 127,
    where 0 is fully opaque and 127 is fully transparent.
    The default value is 64, half transparent.

  - _array_ **$shadow_offset** (optional):
    The offset of text shadows relative to the text, measured in pixels.
    Your array should contain 2 integers:
    the horizontal offset and the vertical offset.
    Use positive values to produce shadows to the right and/or below,
    and negative values to produce shadows to the left and/or above.
    The default is (2, 2).

  - _int_ **$shadow_blur** (optional):
    The blur around text shadows, measured in pixels.
    The default is no blur.

#### get_html()

Arguments:

  - _string_ **$text** (required):
    The text that you want to display.

Return value: A string containing the HTML markup.

  - Each `<img>` tag will have one class, `imgtext`.
    You can attach styles to this class, or manipulate it with JavaScript.
  - The `<img>` tags will be separated by one space character (0x20).
    If multiple spaces are present between words, only one space will be used.
    The same applies to newlines, so it is currently not possible to have
    paragraph breaks inside the generated HTML markup.
    If you need paragraphs, consider using IMGText on one paragraph at a time.
  - There is no need to escape special characters in this string,
    because they are already escaped.
  - The HTML markup should be pasted into a page encoded in UTF-8.

### Miscellaneous Information

IMGText is most beneficial when used in headings. It is not recommended for
body text, which can easily become a mess with hundreds of small images.

IMGText works best when you use font sizes above 20 points.
If you need smaller fonts, consider generating PNG images using large fonts
(2x magnification) and resizing them using CSS height.
This ensures a smooth look, as well as compatibility with high-resolution
(so-called Retina) displays.

IMGText requires PHP 5 and GD. All text must be encoded in UTF-8.

IMGText does not include any font files. Bring your own TTFs!

IMGText is released under the MIT license. It is freely available for
personal, non-profit, commercial, governmental, and any other use.
