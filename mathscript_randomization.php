<?php

class mathscript_randomization
{
    static $unique_pool = array();


    public static function rand($self, $min = 0, $max = null)
    {
        //if max wasn't provided, use the highest number possible
        if(is_null($max))
            $max = mt_getrandmax();

        //generate a random number
        return mt_rand($min, $max);
    }

    public static function between($self, $a, $b)
    {
        //return a number between A and B
        if($a > $b)
            return mt_rand($b, $a);
        else
            return mt_rand($a, $b);
    }

    public static function boolean($self)
    {
        return mt_rand(0, 1); 
    }

    public static function oneOf()
    {
        //get the argument list, and extract $self
        $args = func_get_args();
        $self = array_shift($args);

        //and choose a random element from the array
        return $args[array_rand($args)];
    }

    /**
     * Fills an internal "pool" of unique values with values between $min and $max.
     * Intended to be used with unique_value(), like so:
     *
     * start_unique_between(1, 10);
     *
     * a = unique_value(); //a unique value between 1-10
     * b = unique_value(); //a unique value between 1-10 that != a
     * c = unique_value(); //a unique value between 1-10 that != a and also != b
     * 
     */
    public static function start_unique_between($self, $min, $max)
    {
        //fill the unique pool with every value in the range
        self::$unique_pool = range($min, $max);
    }

    /**
     * Starts a unique pool, filled with the values specified. 
     */
    public static function start_unique_pool()
    {
        //get the argument list, and extract $self
        $args = func_get_args();
        $self = array_shift($args);
        
        //fill the unique pool with the values specified
        self::$unique_pool = $args;
    }

    /**
     * Returns a value from the unique value pool. The value returned will not be returned again, unless the unique pool is reset.
     * Requires that the unique pool was started with one of the start_unique funcitons.
     */
    public static function unique_value($self)
    {  
        //if the unique pool hasn't been set up, or has been delepleted, throw an error
        if(empty(self::$unique_pool))
            return false;

        //pick a random element out of the unique pool
        $index = array_rand(self::$unique_pool);
        $value = self::$unique_pool[$index];

        //remove the element from the unique pool
        unset(self::$unique_pool[$index]);

        //and return its value
        return $value;
    }

}

