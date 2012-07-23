<?php

/**
 * String function extension for MathScript.
 * 
 * @package 
 * @version $id$
 * @copyright 2011, 2012 Binghamton University
 * @author Kyle Temkin <ktemkin@binghamton.edu> 
 * @license GNU Public License, {@link http://www.gnu.org/copyleft/gpl.html}
 */
class mathscript_string
{
	public static function pad_left($self, $input, $pad_length, $pad_with = '0')
	{
		return str_pad($input, $pad_length, $pad_with, STR_PAD_LEFT);
	}

    /**
     * Returns '1' iff the given haystack starts with the needle.
     */
    public static function starts_with($self, $haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle) ? 1 : 0;
    }

    public static function ends_with($self, $haystack, $needle)
    {
        $length = strlen($needle);
        
        //assume all 
        if ($length == 0) 
            return true;
        
        //calculate the start position with respect to the end of the string
        $start  = $length * -1; //negative

        //and check to see if the ends of the string are equal
        return (substr($haystack, $start) === $needle);
    }

}
