<?php

/**
 * Functions to aid in the debugging of MathScripts.
 * 
 * @package     MathScript Core
 * @version $id$
 * @copyright 2011, 2012 Binghamton University
 * @author Kyle Temkin <ktemkin@binghamton.edu> 
 * @license GNU Public License, {@link http://www.gnu.org/copyleft/gpl.html}
 */
class mathscript_debug
{
    /**
     * Turns debug messages on or off; this allows the MathScript engine to display its errors directly to the screen.
     * Not recommended for production use.
     */
    public static function debug_show_errors($self, $on = true)
    {
        echo '<b>WARNING</b>: Show errors is on!</br>'; 

        //set the MathScript engine's debug routines
        $self->debug_show_errors = $on;
    }

    public static function print_to_php($self, $message, $preformat = true)
    {
	if($preformat)
		echo '<pre>';

	print_r($message);

	if($preformat)
		echo '</pre>';
    }

    public static function debug_dump_vars($self)
    {
        echo '<pre>';
        print_r($self->vars());
        echo '</pre>';
    }
    
    public static function debug_dump_interpreter($self)
    {
        echo '<pre>';
        print_r($self);
        echo '</pre>';
    }
}

?>
