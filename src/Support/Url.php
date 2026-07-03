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
        $view = Security::choice($view, [
            'latest', 'movies', 'hot', 'search', 'calendar', 'movie', 'sources',
            'related', 'comments', 'collections', 'collection', 'shares', 'share',
            'requests', 'activities', 'user', 'types', 'genres', 'regions',
        ], 'latest');
        if ($view === 'latest') {
            return self::route('', $params);
        }
        if (in_array($view, ['movie', 'sources', 'related', 'comments'], true)) {
            $slug = isset($params['slug']) ? rawurlencode(Security::scalar($params['slug'])) : '';
            unset($params['slug']);
            if ($slug === '') {
                return self::route('', $params);
            }
            $suffix = $view === 'movie' ? '' : '/' . $view;
            return self::route('movie/' . $slug . $suffix, $params);
        }
        if ($view === 'collection') {
            $slug = isset($params['slug']) ? rawurlencode(Security::scalar($params['slug'])) : '';
            unset($params['slug']);
            return $slug === '' ? self::route('collections', $params) : self::route('collection/' . $slug, $params);
        }
        if ($view === 'share') {
            $id = isset($params['id']) ? (int) $params['id'] : 0;
            unset($params['id']);
            return $id <= 0 ? self::route('shares', $params) : self::route('share/' . $id, $params);
        }
        if ($view === 'user') {
            $username = isset($params['username']) ? rawurlencode(Security::scalar($params['username'])) : '';
            unset($params['username']);
            return $username === '' ? self::route('', $params) : self::route('user/' . $username, $params);
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
