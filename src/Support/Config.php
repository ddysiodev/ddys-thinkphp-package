<?php

namespace Ddys\ThinkPHP\Support;

class Config
{
    public static function all()
    {
        $defaults = include dirname(__DIR__, 2) . '/config/ddys.php';
        $runtime = [];

        if (function_exists('config')) {
            $value = config('ddys');
            if (is_array($value)) {
                $runtime = $value;
            }
        }

        return self::merge($defaults, $runtime);
    }

    public static function get($key, $default = null)
    {
        return Arr::get(self::all(), $key, $default);
    }

    public static function bool($key, $default = false)
    {
        $value = self::get($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_array($value) || is_object($value)) {
            return (bool) $default;
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int($key, $default, $min = null, $max = null)
    {
        $value = self::get($key, $default);
        $value = is_numeric($value) ? (int) $value : (int) $default;
        if ($min !== null && $value < $min) {
            $value = (int) $min;
        }
        if ($max !== null && $value > $max) {
            $value = (int) $max;
        }
        return $value;
    }

    private static function merge(array $base, array $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
