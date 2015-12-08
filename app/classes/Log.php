<?php
namespace App;

class Log
{

    public static function msg($text = '', $time = true, $spaces = 0)
    {
        $text = ($time) ? '[' . date('d-m-Y H:i:s') . '] ' . $text : $text;
        echo $text;

        self::printEOL($spaces);
    }

    public static function printEOL($spaces = 0)
    {
        for($i=0; $i<=$spaces; $i++) {
            echo PHP_EOL;
        }
    }
}
