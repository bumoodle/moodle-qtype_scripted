<?php

/**
 *  MathScript legacy functions.
 *  Primary used for backwards compatability with EvalScript.
 */

class mathscript_legacy
{
    public static function eq($self, $a, $b)
    {
        return ($a == $b) ? 1 : 0;
    }

    public static function gt($self, $a, $b)
    {
        return ($a > $b) ? 1: 0;
    }

    public static function lt($self, $a, $b)
    {
        return ($a < $b) ? 1 : 0;
    }
}

