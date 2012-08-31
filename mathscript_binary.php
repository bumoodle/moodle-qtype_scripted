<?php

class mathscript_binary
{
    //
    // From decimal:
    //

    public static function decbin($self, $a)
    {
        return decbin($a);
    }  

    public static function dechex($self, $a)
    {
        return dechex($a);
    }

    public static function decoct($self, $a)
    {
        return decoct($a);
    }

    //
    // To decimal:
    //

    /**
     * Converts a given argument to binary.    
     */
    public static function bindec($self, $a)
    {
        return bindec($a);
    }

    public static function hexdec($self, $a)
    {
        return hexdec($a);
    }

    public static function octdec($self, $a)
    {
        return octdec($a);
    }


    //
    // To/from ASCII:
    //

    public static function to_ascii($self, $number)
    {
        return chr(intval($number));
    }

    public static function from_ascii($self, $char)
    {
        return ord($char);
    }

    //
    // Zero/sign extended:
    //

    public static function zero_extend($self, $number, $length = null)
    {
        //if no length was provided, extend to the system's word size
        if($length === null)
            $length = PHP_INT_SIZE * 8; 

        //zero-pad the given string
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }

    public static function sign_extend($self, $binary, $length)
    {
        //get the first character of the string
        $pad_width = substr($binary, 0, 1);

        //and repeat it indefinitely
        return str_pad($number, $length, $pad_with, STR_PAD_LEFT);
    }


    /**
     * Removes the leading zeroes from a given binary string.
     */
    public static function zero_trim($self, $number)
    {
         //raise an error on empty numbers
        if(empty($number))
            return false;

        //find the first one
        $first_one = strpos($number, '1');

        //if there were no ones, return 0
        if($first_one === false)
            return '0';

        //return the string starting at the first "1"
        return substr($number, $first_one);    
    }

    /**
     *  Reduces a given two's compliment number to the lest number of bits possible.
     *  Accepts a binary string as its argument.
     */
    public static function sign_trim($self, $number)
    {
        //raise an error on empty numbers
        if(empty($number))
            return false;

        //if the sign bit is one, trim to the first zero
        if(substr($number, 0, 1) == '1')
        {
            //find the first zero
            $first_zero = strpos($number, '0');

            //if there were no zeroes, return the smallest possible two's compliment number that represents -1, '1'
            if($first_zero === false)
                return '1';

            //return the string starting at the first "10"
            return '1'.substr($number, $first_zero);    
        }
        else
        {
            return '0'.self::zero_trim($self, $number); 
        }

    }

    //
    // BCD
    //

    public static function to_packed_bcd($self, $number, $to_binary = 1)
    {
        //convert the number to a string
        $reading = (string)$number;

        //start off with a value of 0, as we'll be OR'ing each of the nibbles in
        $bcd = 0;

        //get the length of the number
        $len = strlen($reading);

        for($i = 0; $i < $len; ++$i)
        {
            //get the value of the individual digit
            $digit = intval($reading[$i]);

            //convert the value to binary
            $digit = decbin($digit);

            //or in the relevant nibble
            $bcd |= $digit << ($i * 4);
        }

        //if the user requested we convert our answer into binary, do so
        if($to_binary)
            return decbin($bcd);    
        else
            return $bcd;

    }

    public static function from_packed_bcd($self, $bcd)
    {
        return self::from_bcd($self, $bcd, 4);
    }

    private static function from_bcd($self, $bcd, $shift_by = 4)
    {
        //start off with an empty string, which will be filled with digits
        $output = '';

        //while there are still non-zero digits in the BCD string
        while($bcd > 0)
        {
            //get four digits of a binary number
            $digit = $bcd & 0xF;

            //if got a result greater than nine, then our number wasn't valid BCD
            if($digit > 9)
                return false;

            //add the digit to our output
            $output = $digit . $output;

            //and shift the number to the right by four
            $bcd >>= 4;
        }

        //return the converted BCD number
        return intval($output);
    }


    //Fixed point:

    public static function to_fixed_point($self, $number, $integer_bits, $binary_places, $include_point = true)
    {
        $integer_modifier = pow(2, $integer_bits);
        $suffix_modifier = pow(2, $binary_places);

        if($number < 0)
        {
            //first, truncate the end of the number
            $number = ceil($number * $suffix_modifier) / $suffix_modifier;

            //then, find its two's compliment
            $number = $integer_modifier + $number;
        }

        //get the pre/postfix suffix
        $prefix = floor($number) % $integer_modifier;
        $suffix = floor($number * $suffix_modifier) % $suffix_modifier;

        //and convert them into the correct binary form
        $prefix = self::zero_extend($self, decbin($prefix), $integer_bits);
        $suffix = self::zero_extend($self, decbin($suffix), $binary_places);

        //then, return the fixed point value
	if($include_point)
		return $prefix.'.'.$suffix;
	else
		return $prefix.$suffix;
    }

    //Hex dump functions

    public static function strhex($self, $string)
    {
        return bin2hex($string);
    }

    public static function hexdump($self, $data, $start_address = 0, $width = 8, $bits_per_entry = 8, $uppercase = true)
    {
        //keep track of the current address, starting from $start_address
        $addr = $start_address;

        //calculate how wide a single entry should be, in hex characters
        //TODO: handle values which aren't nibble
        $entry_width = ceil($bits_per_entry / 4);

        //start a new output buffer
        $dump = '';

        //get a copy of the data's length, so we're not constantly computing it
        $len = strlen($data);

        //determine the width of the maximum address, so we always pad to the right length
        $address_width = max(strlen($addr + ($len / 2)), 4); 
        
        //for each character index in the string
        for($i = 0; $i < $len; ++$i)
        {
            //if we've reached the desired width (or are just starting), start a new line and output the address
            if($i % ($width * $entry_width) === 0)
                $dump .= PHP_EOL.str_pad(dechex($addr), $address_width, '0', STR_PAD_LEFT).'  ';

            //then, place the relevant hex character directly onto the string
            $dump .= substr($data, $i, 1);

            //if we've reached the end of an entry, suffix it with a space, and increment the address
            if(($i % $entry_width) == ($entry_width - 1))
            {   
                $dump .= ' ';
                ++$addr;
            }
        }

        return $dump;

    }

}
