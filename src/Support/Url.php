<?php

namespace Ddys\ThinkPHP\Support;

class Url
{
    public static function route($path = '', array $query = [])
    {
        $prefix = trim((string) Config::get('route_prefix', 'ddys'), '/');
        $path = trim((string) $path, '/');
        $url = '/' . $prefix . ($path === '' ? '' : '/' . $path);
        $query = Security::cleanQuery($query);
        if (!empty($query)) {
            $url .= '?' . http_build_query($query, '', '&');
        }
        return $url;
    }

    public static function page($view = 'latest', array $params = [])
    {
        $view = Security::choice($view, ['latest', 'hot', 'search', 'calendar', 'movie', 'collections', 'requests'], 'latest');
        if ($view === 'latest') {
            return self::route('', $params);
        }
        if ($view === 'movie') {
            $slug = isset($params['slug']) ? rawurlencode(Security::scalar($params['slug'])) : '';
            unset($params['slug']);
            return $slug === '' ? self::route('', $params) : self::route('movie/' . $slug, $params);
        }
        return self::route($view, $params);
    }

    public static function asset($type, $file)
    {
        $assetUrl = trim((string) Config::get('display.asset_url', ''));
        if ($assetUrl !== '') {
            return rtrim($assetUrl, '/') . '/' . trim($type, '/') . '/' . ltrim($file, '/');
        }
        return self::route('assets/' . trim($type, '/') . '/' . ltrim($file, '/'));
    }
}
