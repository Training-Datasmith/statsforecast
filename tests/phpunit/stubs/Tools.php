<?php

class Tools
{
    public static function isSubmit($key)
    {
        if (isset($_POST[$key]) || isset($_GET[$key])) {
            return true;
        }
        if (isset($_POST[$key . '_x']) || isset($_GET[$key . '_x'])) {
            return true;
        }
        if (isset($_POST[$key . '_y']) || isset($_GET[$key . '_y'])) {
            return true;
        }

        return false;
    }

    public static function getValue($key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    public static function substr($string, $start, $length = null)
    {
        if ($length === null) {
            return substr($string, $start);
        }

        return substr($string, $start, $length);
    }

    public static function safeOutput($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function redirectAdmin($url)
    {
        throw new StatsforecastRedirect($url);
    }
}
