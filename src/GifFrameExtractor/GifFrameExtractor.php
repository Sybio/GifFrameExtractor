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
    
    private $gif;
    private $frames;
    
    // Methods
    // ===================================================================================
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->gif = null;
        $this->frames = array();
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
            
            return $this->readBits(ord(substr($this->imagedata[$this->index]["graphicsextension"], $byteIndex, 1)), $bitStart, $bitLength);
        } 
        
        // "dat"
        return $this->readBits(ord(substr($this->imagedata[$this->index]["imagedata"], $byteIndex, 1)), $bitStart, $bitLength);
    }
    
    /**
     * Return the value of 2 ASCII chars
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