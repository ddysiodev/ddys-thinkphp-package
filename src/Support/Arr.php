<?php

namespace Ddys\ThinkPHP\Support;

class Arr
{
    public static function get(array $array, $key, $default = null)
    {
        if ($key === null || $key === '') {
            return $array;
        }
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        $segments = explode('.', (string) $key);
        foreach ($segments as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    public static function only(array $array, array $keys)
    {
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array) && $array[$key] !== '' && $array[$key] !== null) {
                $out[$key] = $array[$key];
            }
        }
        return $out;
    }
}
