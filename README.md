# ================================
# GifFrameExtractor
# ================================

GifFrameExtractor is a PHP class that separe all the frames (and their duration) of an animated GIF

### For what ?

The class helps you to separe all the frames of an animated GIF, for example to watermark them and then to
generate a new watermarked and animated GIF.

### Usage

GifFrameExtractor is really easy to use:

*** 1 - Extraction: ***

```php
$gfe = new GifFrameExtractor();
$gfe->extract('path/images/picture.gif');
```

*** 2 - Getting the frames and their duration: ***

```php
foreach ($gfe->getFrames() as $frame) {
    
    // The frame resource image var
    $img = $frame['image'];
    
    // The frame duration
    $duration = $frame['duration'];
}
```

You can also get separtly an array of images and an array of durations:

```php
$frameImages = $gfe->getFrameImages();
$frameDurations = $gfe->getFrameDurations();
```

*** Option: ***

You can choose if you want to get the original frames (with transparency background) or frames pasted on the first one
with the second parameter of extract() method:

```php
$gfe->extract('path/images/picture.gif', true); // Can get transparency orignal frames
```

This option is false by default. 

### About

The class reuse some part of code of "PHP GIF Animation Resizer" by Taha PAKSU (thanks to him).