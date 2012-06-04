<?

class mathscript_basicmath
{
   public static function mod($self, $dividend, $divisor)
    {
        //calculate the mod of the two-numbers
        $mod = $dividend % $divisor;

        //and compensate for PHP's nonstandard behavior when the dividend is negative
        if($mod < 0)
            $mod += $divisor;

        //return the result
        return $mod;
    }


    public static function pi($self)
    {
        return pi();
    }

    public static function power($self, $base, $exponent)
    {
        return pow($base, $exponent);
    }

    public static function pow($self, $base, $exponent)
    {
        return pow($base, $exponent);
    }

    public static function floor($self, $a)
    {
        return floor($a);
    }

    public static function ceil($self, $a)
    {
        return ceil($a);
    }

    public static function rad2deg($self, $a)
    {
        return rad2deg($a);
    }

    public static function log2($self, $a)
    {
        return log($a, 2);
    }

    public static function round($self, $val, $precision = 0)
    {
        return round($val, $precision);
    }

}

