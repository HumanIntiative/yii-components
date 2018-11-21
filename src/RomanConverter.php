<?php

class RomanConverter
{
    protected static $_roman_chars = array(
        'M'  => 1000,
        'CM' => 900,
        'D'  => 500,
        'CD' => 400,
        'C'  => 100,
        'XC' => 90,
        'L'  => 50,
        'XL' => 40,
        'X'  => 10,
        'IX' => 9,
        'V'  => 5,
        'IV' => 4,
        'I'  => 1
    );

    /**
     * @Create a Roman Numeral from a Number
     * @param int $num
     * @return string
     */
    public static function convert($num)
    {
        $result = null;
        $num = intval($num);

        foreach (static::$_roman_chars as $roman => $number) {
            // divide to get  matches
            $matches = intval($num / $number);

            // assign the roman char * $matches
            $result .= str_repeat($roman, $matches);

            // substract from the number
            $num = $num % $number;
        }

        return $result;
    }
}
