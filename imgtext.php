<?php

/**
 * IMGText: For the rest of us who are stuck without web fonts
 * 
 * URL: http://github.com/kijin/imgtext
 * Version: 0.1.2
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
    
    // Call this method to retrieve the HTML markup for your text.
    
    public function get_html($text, $font, $size, $color = '000', $bg = false, $height = false, $margins = array(0, 0, 0, 0))
    {
        // Compute a hash for this method call, and return cached HTML markup if possible.
        
        $hash = substr(md5(serialize(array($text, $font, $size, $color, $bg, $height, $margins))), 0, 12);
        if ($html = $this->get_cache($hash)) return $html;
        
        // If cached HTML markup is not available, create it now.
        
        $result = $this->create_images($hash, $text, $font, $size, $color, $bg, $height, $margins);
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
    
    protected function create_images($hash, $text, $font, $size, $color, $bg, $height, $margins)
    {
        // Check parameters.
        
        if (trim($text) === '') return array();
        if (!mb_check_encoding($text, 'UTF-8')) throw new IMGTextException('String is not valid UTF-8');
        
        $font_filename = $this->font_dir . '/' . $font . '.' . $this->font_ext;
        if (!file_exists($font_filename)) throw new IMGTextException('Font not found: ' . $font);
        
        $font_size = (int)$size;
        if ($font_size <= 0) throw new IMGTextException('Invalid font size: ' . $size);
        
        if (!preg_match('/^#?(?:[0-9a-f]{3})(?:[0-9a-f]{3})?$/', $color))
        {
            throw new IMGTextException('Invalid text color: ' . $color);
        }
        
        if ($bg !== false && !preg_match('/^#?(?:[0-9a-f]{3})(?:[0-9a-f]{3})?$/', $bg))
        {
            throw new IMGTextException('Invalid background color: ' . $color);
        }
        
        if (!is_array($margins) || count($margins) != 4)
        {
            throw new IMGTextException('Invalid margins');
        }
        
        // Split the text into words.
        
        $words = preg_split('/\\s+/u', $text);
        
        // Parse the margins.
        
        $margin_top = intval($margins[0]);
        $margin_right = intval($margins[1]);
        $margin_bottom = intval($margins[2]);
        $margin_left = intval($margins[3]);
        
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
            
            $imgwidth = $width + $margin_left + $margin_right;
            $imgheight = ($fixed_height > 0) ? $fixed_height : $max_height;
            $imgheight += $margin_top + $margin_bottom;
            
            $img = imageCreateTrueColor($imgwidth, $imgheight);
            
            // Draw the background.
            
            if ($bg === false)  // Transparent background.
            {
                imageSaveAlpha($img, true);
                imageAlphaBlending($img, false);
                $background = imageColorAllocateAlpha($img, 255, 255, 255, 127);
                imageFilledRectangle($img, 0, 0, $imgwidth, $imgheight, $background);
                imageAlphaBlending($img, true);
            }
            else  // Colored background.
            {
                $bgcolors = $this->hex2rgb($bg);
                $background = imageColorAllocate($img, $bgcolors[0], $bgcolors[1], $bgcolors[2]);
                imageFilledRectangle($img, 0, 0, $imgwidth, $imgheight, $background);
            }
            
            // Draw the word.
            
            $fgcolors = $this->hex2rgb($color);
            $foreground = imageColorAllocate($img, $fgcolors[0], $fgcolors[1], $fgcolors[2]);
            imageTTFText($img, $font_size, 0, ($left + $margin_left), ($max_top + $margin_top - 1), $foreground, $font_filename, $w);
            
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
