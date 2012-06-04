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
}
