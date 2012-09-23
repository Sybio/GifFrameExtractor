<?php

/**
 * Extract the frames (and their duration) of a GIF
 * 
 * @version Under development
 * @link https://github.com/Sybio/GifFrameExtractor
 * @author Sybio (Clément Guillemain  / @Sybio01)
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Clément Guillemain
 */
class GifFrameExtractor
{
    // Properties
    // ===================================================================================
    
    /**
     * @var resource
     */
    private $gif;
    
    /**
     * @var array
     */
    private $frames;
    
    /**
     * @var integer (old: index)
     */
    private $frameNumber;
    
    /**
     * @var array (old: imagedata)
     */
    private $frameSources;
    
    // Methods
    // ===================================================================================
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->gif = null;
        $this->frames = array();
        $this->frameNumber = 0;
        $this->frameSources = array();
    }
    
    /**
     * Extract frames of a GIF
     * 
     * @param string $filename GIF filename path
     */
    public function extract($filename)
    {
        $this->gif = imagecreatefromgif($filename);
        
        var_dump(imagegif($this->gif)); exit;
    }
    
    // Internals
    // ===================================================================================
    
    /**
     * Parse frame data (old: parse_image_data)
     */
    private function parseFrameData()
    {
        $this->frameSources[$this->frameNumber]["disposal_method"] = $this->getImageDataBit("ext", 3, 3, 3);
        $this->frameSources[$this->frameNumber]["user_input_flag"] = $this->getImageDataBit("ext", 3, 6, 1);
        $this->frameSources[$this->frameNumber]["transparent_color_flag"] = $this->getImageDataBit("ext", 3, 7, 1);
        $this->frameSources[$this->frameNumber]["delay_time"] = $this->dualByteVal($this->getImageDataByte("ext", 4, 2));
        $this->frameSources[$this->frameNumber]["transparent_color_index"] = ord($this->getImageDataByte("ext", 6, 1));
        $this->frameSources[$this->frameNumber]["offset_left"] = $this->dualByteVal($this->getImageDataByte("dat", 1, 2));
        $this->frameSources[$this->frameNumber]["offset_top"] = $this->dualByteVal($this->getImageDataByte("dat", 3, 2));
        $this->frameSources[$this->frameNumber]["width"] = $this->dualByteVal($this->getImageDataByte("dat", 5, 2));
        $this->frameSources[$this->frameNumber]["height"] = $this->dualByteVal($this->getImageDataByte("dat", 7, 2));
        $this->frameSources[$this->frameNumber]["local_color_table_flag"] = $this->getImageDataBit("dat", 9, 0, 1);
        $this->frameSources[$this->frameNumber]["interlace_flag"] = $this->getImageDataBit("dat", 9, 1, 1);
        $this->frameSources[$this->frameNumber]["sort_flag"] = $this->getImageDataBit("dat", 9, 2, 1);
        $this->frameSources[$this->frameNumber]["color_table_size"] = pow(2, $this->getImageDataBit("dat", 9, 5, 3) + 1) * 3;
        $this->frameSources[$this->frameNumber]["color_table"] = substr($this->frameSources[$this->frameNumber]["imagedata"], 10, $this->frameSources[$this->frameNumber]["color_table_size"]);
        $this->frameSources[$this->frameNumber]["lzw_code_size"] = ord($this->getImageDataByte("dat", 10, 1));
        
        // Decoding
        $this->orgvars[$this->frameNumber]["transparent_color_flag"] = $this->frameSources[$this->frameNumber]["transparent_color_flag"];
        $this->orgvars[$this->frameNumber]["transparent_color_index"] = $this->frameSources[$this->frameNumber]["transparent_color_index"];
        $this->orgvars[$this->frameNumber]["delay_time"] = $this->frameSources[$this->frameNumber]["delay_time"];
        $this->orgvars[$this->frameNumber]["disposal_method"] = $this->frameSources[$this->frameNumber]["disposal_method"];
        $this->orgvars[$this->frameNumber]["offset_left"] = $this->frameSources[$this->frameNumber]["offset_left"];
        $this->orgvars[$this->frameNumber]["offset_top"] = $this->frameSources[$this->frameNumber]["offset_top"];
    }
    
    /**
     * Get the image data byte (old: getImageDataByte)
     * 
     * @param string $type
     * @param integer $start
     * @param integer $length
     * 
     * @return string
     */
    private function getImageDataByte($type, $start, $length)
    {
        if ($type == "ext") {
            
            return substr($this->frameSources[$this->frameNumber]["graphicsextension"], $start, $length);
        }
        
        // "dat"
        return substr($this->frameSources[$this->frameNumber]["imagedata"], $start, $length);
    }
    
    /**
     * Get the image data bit
     * 
     * @param string $type
     * @param integer $byteIndex
     * @param integer $bitStart
     * @param integer $bitLength
     * 
     * @return number
     */
    private function getImageDataBit($type, $byteIndex, $bitStart, $bitLength)
    {
        if ($type == "ext") {
            
            return $this->readBits(ord(substr($this->frameSources[$this->frameNumber]["graphicsextension"], $byteIndex, 1)), $bitStart, $bitLength);
        } 
        
        // "dat"
        return $this->readBits(ord(substr($this->frameSources[$this->frameNumber]["imagedata"], $byteIndex, 1)), $bitStart, $bitLength);
    }
    
    /**
     * Return the value of 2 ASCII chars (old: dualByteVal)
     * 
     * @param string $s
     * 
     * @return integer
     */
    private function dualByteVal($s)
    {
        $i = ord($s[1]) * 256 + ord($s[0]);
        
        return $i;
    }
    
    /**
     * Read the data stream
     * 
     * @param integer $firstLength
     */
    private function readDataStream($firstLength)
    {
        $this->pointerForward($firstLength);
        $length = $this->readByteInt();
        
        if ($length != 0) {
            
            while ($length != 0) {
                
                $this->pointerForward($length);
                $length = $this->readByteInt();
            }
        }
    }
    
    /**
     * Open the gif file
     * 
     * @param string $filename
     */
    private function openFile($filename)
    {
        $this->handle = fopen($filename, "rb");
        $this->pointer = 0;
    }
    
    /**
     * Close the read gif file
     */
    private function closeFile()
    {
        fclose($this->handle);
        $this->handle = 0;
    }
    
    /**
     * Read the file from the beginning to $byteCount in binary
     * 
     * @param integer $byteCount
     * 
     * @return string
     */
    private function readByte($byteCount)
    {
        $data = fread($this->handle, $byteCount);
        $this->pointer += $byteCount;
        
        return $data;
    }
    
    /**
     * Read a byte and return ASCII value
     * 
     * @return integer
     */
    private function readByteInt()
    {
        $data = fread($this->handle, 1);
        $this->pointer++;
        
        return ord($data);
    }
    
    /**
     * Convert a $byte to decimal
     * 
     * @param string $byte
     * @param integer $start
     * @param integer $length
     * 
     * @return number
     */
    private function readBits($byte, $start, $length)
    {
        $bin = str_pad(decbin($byte), 8, "0", STR_PAD_LEFT);
        $data = substr($bin, $start, $length);
        
        return bindec($data);
    }
    
    /**
     * Rewind the file pointer reader
     * 
     * @param integer $length
     */
    private function pointerRewind($length)
    {
        $this->pointer -= $length;
        fseek($this->handle, $this->pointer);
    }
    
    /**
     * Forward the file pointer reader
     * 
     * @param integer $length
     */
    private function pointerForward($length)
    {
        $this->pointer += $length;
        fseek($this->handle, $this->pointer);
    }
    
    /**
     * Get a section of the data from $start to $start + $length
     * 
     * @param integer $start
     * @param integer $length
     * 
     * @return string
     */
    private function dataPart($start, $length)
    {
        fseek($this->handle, $start);
        $data = fread($this->handle, $length);
        fseek($this->handle, $this->pointer);
        
        return $data;
    }
    
    /**
     * Check if a character if a byte
     * 
     * @param integer $byte
     * 
     * @return boolean
     */
    private function checkByte($byte)
    {
        if (fgetc($this->handle) == chr($byte)) {
            
            fseek($this->handle, $this->pointer);
            return true;
        }
        
        fseek($this->handle, $this->pointer);

        return false;
    }
    
    /**
     * Check the end of the file
     * 
     * @return boolean
     */
    private function checkEOF()
    {
        if (fgetc($this->handle) === false) {
            
            return true;
        }
            
        fseek($this->handle, $this->pointer);
        
        return false;
    }
}