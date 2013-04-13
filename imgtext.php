<?php

/**
 * IMGText: For the rest of us who are stuck without web fonts
 * 
 * URL: http://github.com/kijin/imgtext
 * Version: 0.1.3
 * 
 * Copyright (c) 2013, Kijin Sung <kijin@kijinsung.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class IMGText
{
    // Set the URL prefix for generated images. Must be accessible over the Web.
    
    public $cache_url_prefix;
    
    // Set the local directory where the same images will be stored. Must be writable.
    
    public $cache_local_dir;
    
    // Set the local directory where fonts are stored.
    
    public $font_dir;
    
    // Set the extension for fonts. Don't change unless you're sure.
    
    public $font_ext = 'ttf';
    
    // Set the name of the font, without the extension.
    
    public $font_name;
    
    // Set the size of the font.
    
    public $font_size;
    
    // Set the image height. The default is automatic.
    
    public $image_height = false;
    
    // Set the text color.
    
    public $color = '#000';
    
    // Set the background color. The default is transparent.
    
    public $background_color = false;
    
    // Set the padding amount.
    
    public $padding = array(0, 0, 0, 0);
    
    // Set the shadow properties.
    
    public $shadow = false;
    public $shadow_color = '#000';
    public $shadow_opacity = 64;
    public $shadow_offset = array(2, 2);
    public $shadow_blur = 0;
	
    // Call this method to retrieve the HTML markup for your text.
    
    public function get_html($text, $font_name = null, $font_size = null, $color = null, $background_color = null, $image_height = null, $padding = null)
    {
        // Fill default values.
        
        if ($font_name === null) $font_name = $this->font_name;
        if ($font_size === null) $font_size = $this->font_size;
        if ($color === null) $color = $this->color;
        if ($background_color === null) $background_color = $this->background_color;
        if ($image_height === null) $image_height = $this->image_height;
        if ($padding === null) $padding = $this->padding;
        
        // Compute a hash for this method call, and return cached HTML markup if possible.
        
        $hash = substr(md5(serialize(array($text, $font_name, $font_size, $color, $background_color, $image_height, $padding))), 0, 12);
        if ($html = $this->get_cache($hash)) return $html;
        
        // If cached HTML markup is not available, create it now.
        
        $result = $this->create_images($hash, $text, $font_name, $font_size, $color, $background_color, $image_height, $padding);
        $html = '';
        
        foreach ($result as $img)
        {
            $word = htmlspecialchars($img['word'], ENT_QUOTES, 'UTF-8');
            $path = htmlspecialchars($img['path'], ENT_QUOTES, 'UTF-8');
            $html .= '<img class="imgtext" src="' . $path . '" alt="' . $word . '" title="" /> ';
        }
        
        // Save the HTML markup to cache, and return it.
        
        $this->set_cache($hash, $html);
        return $html;
    }
    
    // This method retrieves cached HTML markup.
    
    protected function get_cache($hash)
    {
        $filename = $this->cache_local_dir . '/imgtext.' . $hash . '.cached-markup.txt';
        if (file_exists($filename)) return file_get_contents($filename);
        return false;
    }
    
    // This method stores HTML markup in the cache.
    
    protected function set_cache($hash, $html)
    {
        $filename = $this->cache_local_dir . '/imgtext.' . $hash . '.cached-markup.txt';
        file_put_contents($filename, $html);
    }
    
    // This method creates the actual PNG images.
    
    protected function create_images($hash, $text, $font_name, $font_size, $color, $background_color, $height, $padding)
    {
        // Check parameters.
        
        if (trim($text) === '') return array();
        if (!mb_check_encoding($text, 'UTF-8')) throw new IMGTextException('String is not valid UTF-8');
        
        $font_filename = $this->font_dir . '/' . $font_name . '.' . $this->font_ext;
        if (!file_exists($font_filename)) throw new IMGTextException('Font not found: ' . $font);
        
        $font_size = (int)$font_size;
        if ($font_size <= 0) throw new IMGTextException('Invalid font size: ' . $size);
        
        if (!preg_match('/^#?(?:[0-9a-f]{3})(?:[0-9a-f]{3})?$/', $color))
        {
            throw new IMGTextException('Invalid text color: ' . $color);
        }
        
        if ($background_color !== false && !preg_match('/^#?(?:[0-9a-f]{3})(?:[0-9a-f]{3})?$/', $background_color))
        {
            throw new IMGTextException('Invalid background color: ' . $color);
        }
        
        if (!is_array($padding) || count($padding) != 4)
        {
            throw new IMGTextException('Invalid padding');
        }
        
        // Split the text into words.
        
        $words = preg_split('/\\s+/u', $text);
        
        // Parse the padding amount.
        
        $padding_top = intval($padding[0]);
        $padding_right = intval($padding[1]);
        $padding_bottom = intval($padding[2]);
        $padding_left = intval($padding[3]);
        
        // Get size information for each word.
        
        $fragments = array();
        $fixed_height = intval($height);
        $max_height = 0;
        $max_top = 0;
        
        foreach ($words as $w)
        {
            $w = trim($w);
            if ($w === '') continue;
            
            // Get the bounding box size.
            
            $bounds = imageTTFBBox($font_size, 0, $font_filename, $w);
            
            // Get more useful information from GD's return values.
            
            $width = $bounds[2] - $bounds[0];
            $height = $bounds[3] - $bounds[5];
            $left = $bounds[6] * -1;
            $top = $bounds[7] * -1;
            
            // Update the max height/top values if necessary.
            
            if ($height > $max_height) $max_height = $height;
            if ($top > $max_top) $max_top = $top;
            
            $fragments[] = array($w, $width, $height, $left, $top);
        }
        
        // Create images for each word.
        
        $count = 1;
        $return = array();
        
        foreach ($fragments as $f)
        {
            list($w, $width, $height, $left, $top) = $f;
            
            $img_width = $width + $padding_left + $padding_right;
            $img_height = ($fixed_height > 0) ? $fixed_height : $max_height;
            $img_height += $padding_top + $padding_bottom;
            
            if ($this->shadow)
            {
                $img_width += $this->shadow_offset[0];
                $img_height += $this->shadow_offset[1];
            }
            
            $img = imageCreateTrueColor($img_width, $img_height);
            
            // Draw the background.
            
            if ($background_color === false)  // Transparent background.
            {
                imageSaveAlpha($img, true);
                imageAlphaBlending($img, false);
                $img_background_color = imageColorAllocateAlpha($img, 255, 255, 255, 127);
                imageFilledRectangle($img, 0, 0, $img_width, $img_height, $img_background_color);
                imageAlphaBlending($img, true);
            }
            else  // Colored background.
            {
                $img_background_colors = $this->hex2rgb($background_color);
                $img_background_color = imageColorAllocate($img, $img_background_colors[0], $img_background_colors[1], $img_background_colors[2]);
                imageFilledRectangle($img, 0, 0, $img_width, $img_height, $img_background_color);
            }
            
            // Draw the shadow.
            
            if ($this->shadow)
            {
                if ($background_color === false)  // Transparent background needs manual alpha merge, because GD can't blur transparent images.
                {
                    $shadow_colors = $this->hex2rgb($this->shadow_color);
                    $temp = imageCreateTrueColor($img_width, $img_height);
                    imageSaveAlpha($temp, true);
                    imageFilledRectangle($temp, 0, 0, $img_width, $img_height, imageColorAllocate($temp, 127, 127, 127));
                    
                    $temp_text_color = imageColorAllocate($temp, $this->shadow_opacity, $this->shadow_opacity, $this->shadow_opacity);
                    imageTTFText($temp, $font_size, 0, ($left + $padding_left + $this->shadow_offset[0] - 1), ($max_top + $padding_top + $this->shadow_offset[1] - 1), $temp_text_color, $font_filename, $w);
                    for ($i = 0; $i < $this->shadow_blur; $i++)
                    {
                        imageFilter($temp, IMG_FILTER_GAUSSIAN_BLUR);
                    }
                    for ($x = 0; $x < $img_width; $x++)
                    {
                        for ($y = 0; $y < $img_height; $y++)
                        {
                            $alpha = imageColorAt($temp, $x, $y) & 0xFF;
                            imageSetPixel($img, $x, $y, imageColorAllocateAlpha($img, $shadow_colors[0], $shadow_colors[1], $shadow_colors[2], $alpha));
                        }
                    }
                }
                else  // Colored background works fine without any additional code.
                {
                    $shadow_colors = $this->hex2rgb($this->shadow_color);
                    $shadow_color = imageColorAllocateAlpha($img, $shadow_colors[0], $shadow_colors[1], $shadow_colors[2], $this->shadow_opacity);
                    imageTTFText($img, $font_size, 0, ($left + $padding_left + $this->shadow_offset[0] - 1), ($max_top + $padding_top + $this->shadow_offset[1] - 1), $shadow_color, $font_filename, $w);
                    for ($i = 0; $i < $this->shadow_blur; $i++)
                    {
                        imageFilter($img, IMG_FILTER_GAUSSIAN_BLUR);
                    }
                }
            }
            
            // Draw the word.
            
            $text_colors = $this->hex2rgb($color);
            $text_color = imageColorAllocate($img, $text_colors[0], $text_colors[1], $text_colors[2]);
            imageTTFText($img, $font_size, 0, ($left + $padding_left - 1), ($max_top + $padding_top - 1), $text_color, $font_filename, $w);
			
            // Save to a PNG file.
            
            $filename = '/imgtext.' . $hash . '.word-' . str_pad($count, 3, '0', STR_PAD_LEFT) . '.png';
            imagePNG($img, $this->cache_local_dir . $filename);
            imageDestroy($img);
            
            // Add information about this word to the return array.
            
            $return[] = array('word' => $w, 'path' => $this->cache_url_prefix . $filename);
            $count++;
        }
        
        // Returns a list of dictionaries, each containing a word and the corresponding image URL.
        
        return $return;
    }
    
    // Convert CSS hex color to RGB.
    
    public function hex2rgb($hex)
    {
        // Remove # from the beginning, if present.
        
        $hex = str_replace('#', '', $hex);
        
        // Compatible with both 3-byte and 6-byte syntax. Default is black.
        
        if (strlen($hex) == 3)
        {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        }
        elseif (strlen($hex) == 6)
        {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        else
        {
            $r = $g = $b = 0;
        }
        
        // Returns an array with 3 elements (R, G, B).
        
        return array($r, $g, $b);
    }
}

// Exception class.

class IMGTextException extends Exception { }
