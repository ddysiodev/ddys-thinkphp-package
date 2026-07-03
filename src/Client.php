<?php

namespace Ddys\ThinkPHP;

use Ddys\ThinkPHP\Cache\Repository as CacheRepository;
use Ddys\ThinkPHP\Exception\DdysException;
use Ddys\ThinkPHP\Exception\NetworkException;
use Ddys\ThinkPHP\Exception\ParseException;
use Ddys\ThinkPHP\Exception\TimeoutException;
use Ddys\ThinkPHP\Support\Arr;
use Ddys\ThinkPHP\Support\Config;
use Ddys\ThinkPHP\Support\Security;

class Client
{
    const DEFAULT_BASE_URL = 'https://ddys.io/api/v1';
    const VERSION = '0.1.1';

    protected $config;
    protected $cache;
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;
    protected $userAgent;

    public function __construct(array $config = null, CacheRepository $cache = null)
    {
        $this->config = $config ?: Config::all();
        $this->cache = $cache ?: new CacheRepository(null, $this->config);
        $this->baseUrl = Security::normalizeBaseUrl(Arr::get($this->config, 'api_base_url', self::DEFAULT_BASE_URL), self::DEFAULT_BASE_URL);
        $this->apiKey = trim((string) Arr::get($this->config, 'api_key', ''));
        $this->timeout = Security::intRange(Arr::get($this->config, 'timeout', 12), 12, 1, 60);
        $this->userAgent = (string) Arr::get($this->config, 'user_agent', 'ddys-thinkphp-package/' . self::VERSION);
    }

    public function request($method, $path, array $query = [], $body = null, array $options = [])
    {
        $method = strtoupper((string) $method);
        $path = '/' . ltrim((string) $path, '/');
        $query = Security::cleanQuery($query);
        $url = $this->buildUrl($path, $query);
        $auth = !empty($options['auth']);

        if ($auth && $this->apiKey === '') {
            throw new DdysException('低端影视 API Key 尚未配置。', 401, $method, $path);
        }

        $useCache = $method === 'GET' && empty($options['no_cache']);
        $cacheKey = $this->cacheKey($method, $path, $query);
        if ($useCache) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: ' . $this->userAgent,
        ];
        if ($auth) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $raw = $this->http($method, $url, $body, $headers, $path);
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new ParseException('低端影视 API 返回了无效 JSON。', 0, $method, $path, $raw);
        }
        if (isset($json['success']) && $json['success'] === false) {
            $message = isset($json['message']) ? $json['message'] : '低端影视 API 请求失败。';
            throw new DdysException($message, 0, $method, $path, $json);
        }
        if (!isset($json['success']) || $json['success'] !== true) {
            throw new ParseException('低端影视 API 响应缺少 success=true。', 0, $method, $path, $json);
        }

        if ($useCache) {
            $ttl = isset($options['cache_ttl']) ? (int) $options['cache_ttl'] : $this->cache->ttlForPath($path);
            $this->cache->set($cacheKey, $json, $ttl);
        }

        return $json;
    }

    public function get($path, array $query = [], array $options = [])
    {
        return $this->request('GET', $path, $query, null, $options);
    }

    public function post($path, array $body = [], array $options = [])
    {
        return $this->request('POST', $path, [], $body, $options);
    }

    public function delete($path, array $options = [])
    {
        return $this->request('DELETE', $path, [], null, $options);
    }

    public function data($path, array $query = [], array $options = [])
    {
        $envelope = $this->get($path, $query, $options);
        return isset($envelope['data']) ? $envelope['data'] : null;
    }

    public function paginated($path, array $query = [], array $options = [])
    {
        $envelope = $this->get($path, $query, $options);
        return [
            'data' => isset($envelope['data']) && is_array($envelope['data']) ? $envelope['data'] : [],
            'meta' => isset($envelope['meta']) && is_array($envelope['meta']) ? $envelope['meta'] : [
                'total' => isset($envelope['data']) && is_array($envelope['data']) ? count($envelope['data']) : 0,
                'page' => 1,
                'per_page' => isset($envelope['data']) && is_array($envelope['data']) ? count($envelope['data']) : 0,
                'total_pages' => 1,
            ],
        ];
    }

    public function movies(array $params = [])
    {
        return $this->paginated('/movies', Security::buildQuery($params, ['type', 'genre', 'region', 'year', 'sort', 'page', 'per_page']));
    }

    public function latest(array $params = [])
    {
        return $this->data('/latest', Security::buildQuery($params, ['type', 'limit']));
    }

    public function hot(array $params = [])
    {
        return $this->data('/hot', Security::buildQuery($params, ['type', 'genre', 'region', 'limit']));
    }

    public function search(array $params)
    {
        return $this->paginated('/search', Security::buildQuery($params, ['q', 'type', 'page', 'per_page']));
    }

    public function suggest($q)
    {
        return $this->data('/suggest', ['q' => Security::scalar($q)]);
    }

    public function calendar(array $params = [])
    {
        return $this->data('/calendar', Security::buildQuery($params, ['year', 'month']));
    }

    public function movie($slug)
    {
        return $this->data('/movies/' . rawurlencode(Security::scalar($slug)));
    }

    public function sources($slug)
    {
        return $this->data('/movies/' . rawurlencode(Security::scalar($slug)) . '/sources');
    }

    public function related($slug)
    {
        return $this->data('/movies/' . rawurlencode(Security::scalar($slug)) . '/related');
    }

    public function comments($slug, array $params = [])
    {
        return $this->paginated('/movies/' . rawurlencode(Security::scalar($slug)) . '/comments', Security::buildQuery($params, ['page', 'per_page']));
    }

    public function types()
    {
        return $this->data('/types');
    }

    public function genres()
    {
        return $this->data('/genres');
    }

    public function regions()
    {
        return $this->data('/regions');
    }

    public function collections(array $params = [])
    {
        return $this->paginated('/collections', Security::buildQuery($params, ['page', 'per_page']));
    }

    public function collection($slug, array $params = [])
    {
        $envelope = $this->get('/collections/' . rawurlencode(Security::scalar($slug)), Security::buildQuery($params, ['page', 'per_page']));
        $data = isset($envelope['data']) ? $envelope['data'] : [];
        if (is_array($data) && isset($envelope['meta'])) {
            $data['meta'] = $envelope['meta'];
        }
        return $data;
    }

    public function shares(array $params = [])
    {
        return $this->paginated('/shares', Security::buildQuery($params, ['page', 'per_page']));
    }

    public function share($id)
    {
        return $this->data('/shares/' . (int) $id);
    }

    public function requests(array $params = [])
    {
        return $this->paginated('/requests', Security::buildQuery($params, ['page', 'per_page']));
    }

    public function createRequest(array $input)
    {
        return $this->unwrap($this->post('/requests', $input, ['auth' => true, 'no_cache' => true]));
    }

    public function activities(array $params = [])
    {
        return $this->paginated('/activities', Security::buildQuery($params, ['type', 'page', 'per_page']));
    }

    public function user($username)
    {
        return $this->data('/user/' . rawurlencode(Security::scalar($username)));
    }

    public function me()
    {
        return $this->unwrap($this->get('/me', [], ['auth' => true, 'no_cache' => true]));
    }

    public function createComment(array $input)
    {
        return $this->unwrap($this->post('/comments', $input, ['auth' => true, 'no_cache' => true]));
    }

    public function deleteComment($id)
    {
        return $this->unwrap($this->delete('/comments/' . (int) $id, ['auth' => true, 'no_cache' => true]));
    }

    public function reportInvalidResource(array $input)
    {
        return $this->unwrap($this->post('/report', $input, ['auth' => true, 'no_cache' => true]));
    }

    public function follow($username)
    {
        return $this->setFollow($username, 'follow');
    }

    public function unfollow($username)
    {
        return $this->setFollow($username, 'unfollow');
    }

    public function setFollow($username, $action)
    {
        return $this->unwrap($this->post('/follow', [
            'username' => Security::scalar($username),
            'action' => Security::choice($action, ['follow', 'unfollow'], 'follow'),
        ], ['auth' => true, 'no_cache' => true]));
    }

    public function proxy($route, array $query = [])
    {
        $route = strtolower(Security::scalar($route, 'latest'));
        $allowed = Arr::get($this->config, 'security.proxy_allow_routes', []);
        if (!in_array($route, is_array($allowed) ? $allowed : [], true)) {
            throw new DdysException('Route not allowed.', 403, 'GET', '/proxy');
        }
        $path = $this->proxyPath($route, $query);
        if ($path === '') {
            throw new DdysException('Invalid route parameters.', 400, 'GET', '/proxy');
        }
        return $this->get($path, Security::buildQuery($query, ['type', 'genre', 'region', 'year', 'sort', 'page', 'per_page', 'limit', 'q', 'month']));
    }

    public function normalizeRequestInput(array $input)
    {
        $title = Security::substr(Security::scalar(Arr::get($input, 'title')), 0, 255);
        if ($title === '') {
            throw new DdysException('请填写片名。', 400, 'POST', '/requests');
        }

        $year = Security::scalar(Arr::get($input, 'year'));
        return [
            'title' => $title,
            'year' => $year === '' ? '' : Security::intRange($year, 0, 1900, 2099),
            'type' => Security::choice(Arr::get($input, 'type', ''), ['movie', 'series', 'variety', 'anime', ''], ''),
            'description' => Security::substr(Security::scalar(Arr::get($input, 'description')), 0, 1000),
            'douban_id' => Security::substr(Security::scalar(Arr::get($input, 'douban_id')), 0, 30),
        ];
    }

    protected function unwrap(array $envelope)
    {
        return isset($envelope['data']) ? $envelope['data'] : $envelope;
    }

    protected function proxyPath($route, array $query)
    {
        $slug = Security::scalar(Arr::get($query, 'slug'));
        $id = Security::scalar(Arr::get($query, 'id'));
        $username = Security::scalar(Arr::get($query, 'username'));
        switch ($route) {
            case 'movies': return '/movies';
            case 'latest': return '/latest';
            case 'hot': return '/hot';
            case 'search': return '/search';
            case 'suggest': return '/suggest';
            case 'calendar': return '/calendar';
            case 'movie': return $slug === '' ? '' : '/movies/' . rawurlencode($slug);
            case 'sources': return $slug === '' ? '' : '/movies/' . rawurlencode($slug) . '/sources';
            case 'related': return $slug === '' ? '' : '/movies/' . rawurlencode($slug) . '/related';
            case 'comments': return $slug === '' ? '' : '/movies/' . rawurlencode($slug) . '/comments';
            case 'collections': return '/collections';
            case 'collection': return $slug === '' ? '' : '/collections/' . rawurlencode($slug);
            case 'shares': return '/shares';
            case 'share': return $id === '' ? '' : '/shares/' . (int) $id;
            case 'requests': return '/requests';
            case 'activities': return '/activities';
            case 'user': return $username === '' ? '' : '/user/' . rawurlencode($username);
            case 'types': return '/types';
            case 'genres': return '/genres';
            case 'regions': return '/regions';
        }
        return '';
    }

    protected function buildUrl($path, array $query)
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query, '', '&');
        }
        return $url;
    }

    protected function cacheKey($method, $path, array $query)
    {
        return 'api:' . md5(strtoupper($method) . '|' . $this->baseUrl . '|' . $path . '|' . serialize($query));
    }

    protected function http($method, $url, $body, array $headers, $endpoint)
    {
        if (function_exists('curl_init')) {
            return $this->curl($method, $url, $body, $headers, $endpoint);
        }
        return $this->stream($method, $url, $body, $headers, $endpoint);
    }

    protected function curl($method, $url, $body, array $headers, $endpoint)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encodeJson($body));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            if ($err && stripos($err, 'timed out') !== false) {
                throw new TimeoutException('低端影视 API 请求超时。', $status, $method, $endpoint);
            }
            throw new NetworkException($err ?: '低端影视 API 网络请求失败。', $status, $method, $endpoint);
        }
        if ($status >= 400) {
            throw new DdysException('低端影视 API HTTP ' . $status . '。', $status, $method, $endpoint, $raw);
        }
        return $raw;
    }

    protected function stream($method, $url, $body, array $headers, $endpoint)
    {
        $opts = [
            'http' => [
                'method' => $method,
                'timeout' => $this->timeout,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
            ],
        ];
        if ($body !== null) {
            $opts['http']['header'] .= "\r\nContent-Type: application/json";
            $opts['http']['content'] = $this->encodeJson($body);
        }
        $raw = @file_get_contents($url, false, stream_context_create($opts));
        if ($raw === false) {
            throw new NetworkException('当前 PHP 环境无法请求低端影视 API。', 0, $method, $endpoint);
        }
        $status = $this->streamStatus(isset($http_response_header) ? $http_response_header : []);
        if ($status >= 400) {
            throw new DdysException('低端影视 API HTTP ' . $status . '。', $status, $method, $endpoint, $raw);
        }
        return $raw;
    }

    protected function streamStatus($headers)
    {
        if (!is_array($headers) || empty($headers[0])) {
            return 0;
        }
        return preg_match('#\s([0-9]{3})\s#', $headers[0], $matches) ? (int) $matches[1] : 0;
    }

    protected function encodeJson($value)
    {
        $flags = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
        return json_encode($value, $flags);
    }
}
