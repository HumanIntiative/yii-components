<?php

class CurrencyHelper extends CComponent
{
    protected static $satuan = [
        1=>'Satu', 2=>'Dua', 3=>'Tiga', 4=>'Empat', 5=>'Lima', 6=>'Enam',
        7=>'Tujuh', 8=>'Delapan', 9=>'Sembilan', 10=>'Sepuluh',11=>'Sebelas',
        100=>'Seratus', 1000=>'Seribu',
    ];

    public static function terbilang($value)
    {
        echo self::recurrence($value);
    }

    // Batas: Milyar (10^9)
    protected static function recurrence($x)
    {
        $abil = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        if ($x < 12) {
            return " " . $abil[$x];
        } elseif ($x < 20) {
            return static::recurrence($x - 10) . "belas";
        } elseif ($x < 100) {
            return static::recurrence($x / 10) . " Puluh" . static::recurrence($x % 10);
        } elseif ($x < 200) {
            return " Seratus" . static::recurrence($x - 100);
        } elseif ($x < 1000) {
            return static::recurrence($x / 100) . " Ratus" . static::recurrence($x % 100);
        } elseif ($x < 2000) {
            return " Seribu" . static::recurrence($x - 1000);
        } elseif ($x < 1000000) {
            return static::recurrence($x / 1000) . " Ribu" . static::recurrence($x % 1000);
        } elseif ($x < 1000000000) {
            return static::recurrence($x / 1000000) . " Juta" . static::recurrence($x % 1000000);
        } elseif ($x < 1000000000000) {
            return static::recurrence($x / 1000000000000) . " Milyar" . static::recurrence($x % 1000000000000);
        }
    }
}
