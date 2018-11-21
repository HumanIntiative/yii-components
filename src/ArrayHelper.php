<?php

class ArrayHelper extends CComponent
{
    public static function prettyPrint($arr)
    {
        echo '<pre>';
        var_dump($arr);
        echo '</pre>';
    }
}
