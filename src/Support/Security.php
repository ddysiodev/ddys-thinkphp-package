<?php

namespace Ddys\ThinkPHP\Support;

class Security
{
    public static function scalar($value, $default = '')
    {
        if (is_array($value) || is_object($value)) {
            return $default;
        }
        return trim(str_replace("\0", '', (string) $value));
    }

    public static function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function attr($value)
    {
        return self::h($value);
    }

    public static function bool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower(self::scalar($value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function intRange($value, $fallback, $min, $max)
    {
        if (!is_numeric($value)) {
            return (int) $fallback;
        }
        $value = (int) $value;
        if ($value < $min) {
            return (int) $min;
        }
        if ($value > $max) {
            return (int) $max;
        }
        return $value;
    }

    public static function choice($value, array $allowed, $fallback)
    {
        $value = strtolower(self::scalar($value));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public static function normalizeBaseUrl($value, $fallback)
    {
        $value = self::scalar($value);
        if ($value === '' || !preg_match('#^https?://#i', $value)) {
            return $fallback;
        }
        $parts = parse_url($value);
        if (empty($parts['scheme']) || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass'])) {
            return $fallback;
        }
        return rtrim($value, '/');
    }

    public static function safeMediaUrl($value)
    {
        $value = self::scalar($value);
        return preg_match('#^https?://#i', $value) ? $value : '';
    }

    public static function cleanQuery(array $params)
    {
        $out = [];
        foreach ($params as $key => $value) {
            $value = self::scalar($value);
            if ($value !== '') {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public static function normalizeQueryValue($key, $value)
    {
        $value = self::scalar($value);
        if ($value === '') {
            return '';
        }
        if ($key === 'limit') {
            return self::intRange($value, 12, 1, Config::int('security.max_limit', 50, 1, 200));
        }
        if ($key === 'per_page') {
            return self::intRange($value, 12, 1, Config::int('security.max_per_page', 50, 1, 200));
        }
        if ($key === 'page') {
            return self::intRange($value, 1, 1, 999);
        }
        if ($key === 'year') {
            return self::intRange($value, 0, 0, 2099);
        }
        if ($key === 'month') {
            return self::intRange($value, 0, 0, 12);
        }
        return $value;
    }

    public static function buildQuery(array $source, array $keys)
    {
        $out = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $source)) {
                continue;
            }
            $value = self::scalar($source[$key]);
            if ($value !== '') {
                $out[$key] = self::normalizeQueryValue($key, $value);
            }
        }
        return $out;
    }

    public static function nonce($action = 'default', $bucket = null)
    {
        if ($bucket === null) {
            $bucket = floor(time() / 43200);
        }
        return hash_hmac('sha256', $action . '|' . $bucket, self::nonceSecret());
    }

    public static function checkNonce($token, $action = 'default')
    {
        $token = self::scalar($token);
        if ($token === '') {
            return false;
        }
        $bucket = floor(time() / 43200);
        return self::hashEquals(self::nonce($action, $bucket), $token)
            || self::hashEquals(self::nonce($action, $bucket - 1), $token);
    }

    public static function currentIp()
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $parts = explode(',', (string) $_SERVER[$key]);
                $value = trim($parts[0]);
                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return $value;
                }
            }
        }
        return 'unknown';
    }

    public static function substr($value, $start, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr((string) $value, $start, $length, 'UTF-8');
        }
        return substr((string) $value, $start, $length);
    }

    protected static function nonceSecret()
    {
        $secret = (string) Config::get('request_form.nonce_secret', '');
        if ($secret !== '') {
            return $secret;
        }
        if (function_exists('config')) {
            $appKey = config('app.app_key');
            if ($appKey) {
                return (string) $appKey;
            }
        }
        $envKey = getenv('APP_KEY');
        if ($envKey) {
            return (string) $envKey;
        }
        return __DIR__ . '|ddys-thinkphp-package';
    }

    protected static function hashEquals($known, $user)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known, $user);
        }
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < strlen($known); $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $result === 0;
    }
}
