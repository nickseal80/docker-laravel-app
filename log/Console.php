<?php

namespace log;

class Console
{
    public const COLOR_STD_OUT = 'yellow';
    public const COLOR_SUCCESS = 'green';
    public const COLOR_ERROR = 'red';
    public const COLOR_ASK = 'light_gray';

    static array $foreground_colors = [
        'bold'       => '1', 'dim' => '2',
        'black'      => '0;30', 'dark_gray' => '1;30',
        'blue'       => '0;34', 'light_blue' => '1;34',
        'green'      => '0;32', 'light_green' => '1;32',
        'cyan'       => '0;36', 'light_cyan' => '1;36',
        'red'        => '0;31', 'light_red' => '1;31',
        'purple'     => '0;35', 'light_purple' => '1;35',
        'brown'      => '0;33', 'yellow' => '1;33',
        'light_gray' => '0;37', 'white' => '1;37',
        'normal'     => '0;39',
    ];

    static array $background_colors = [
        'black' => '40', 'red' => '41',
        'green' => '42', 'yellow' => '43',
        'blue'  => '44', 'magenta' => '45',
        'cyan'  => '46', 'light_gray' => '47',
    ];

    static array $options = [
        'underline' => '4', 'blink' => '5',
        'reverse'   => '7', 'hidden' => '8',
    ];

    static string $EOF = "\n";

    /**
     * Logs a string to console.
     * @param string $str Input String
     * @param string $color Text Color
     * @param boolean $newline Append EOF?
     * @param string|null $background_color
     * @return void [type]              Formatted output
     */
    public static function log(
        string $str = '',
        string $color = 'normal',
        bool $newline = true,
        string $background_color = null
    ): void {
        $str = $newline ? $str . self::$EOF : $str;

        echo self::$color($str, $background_color);
    }

    public static function ask(string $question, string $color, string $background_color = null): bool|string
    {
        echo self::$color($question, $background_color);
        return readline();
    }

    public static function askPassword(string $question, $color): bool|string
    {
        echo self::$color($question, null);
        system('stty -echo');
        $password = rtrim(fgets(STDIN));
        system('stty echo');
        echo self::$EOF;

        return $password;
    }

    /**
     * Catches static calls (Wildcard)
     * @param string $foreground_color Text Color
     * @param array $args Options
     * @return string Colored string
     */
    public static function __callStatic(string $foreground_color, array $args)
    {
        $string = $args[0];
        $colored_string = "";

        // Check if given foreground color found
        if (isset(self::$foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        } else {
            die($foreground_color . ' not a valid color');
        }

        array_shift($args);

        foreach ($args as $option) {
            // Check if given background color found
            if (isset(self::$background_colors[$option])) {
                $colored_string .= "\033[" . self::$background_colors[$option] . "m";
            } elseif (isset(self::$options[$option])) {
                $colored_string .= "\033[" . self::$options[$option] . "m";
            }
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;

    }

    /**
     * Plays a bell sound in console (if available)
     * @param integer $count Bell play count
     */
    public static function bell(int $count = 1): void
    {
        echo str_repeat("\007", $count);
    }
}
