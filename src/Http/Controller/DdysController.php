<?php

namespace Ddys\ThinkPHP\Http\Controller;

use Ddys\ThinkPHP\Cache\Repository as CacheRepository;
use Ddys\ThinkPHP\Client;
use Ddys\ThinkPHP\Exception\DdysException;
use Ddys\ThinkPHP\Renderer;
use Ddys\ThinkPHP\Support\Arr;
use Ddys\ThinkPHP\Support\Config;
use Ddys\ThinkPHP\Support\Security;
use think\Response;

class DdysController
{
    protected $client;
    protected $renderer;
    protected $cache;

    public function __construct(Client $client = null, Renderer $renderer = null, CacheRepository $cache = null)
    {
        $this->client = $client ?: (function_exists('app') ? app('ddys') : new Client());
        $this->renderer = $renderer ?: new Renderer($this->client);
        $this->cache = $cache ?: new CacheRepository();
    }

    public function index()
    {
        return $this->html($this->renderer->fullPage('latest', $this->params()));
    }

    public function movies()
    {
        return $this->html($this->renderer->fullPage('movies', $this->params()));
    }

    public function hot()
    {
        return $this->html($this->renderer->fullPage('hot', $this->params()));
    }

    public function search()
    {
        return $this->html($this->renderer->fullPage('search', $this->params()));
    }

    public function calendar()
    {
        return $this->html($this->renderer->fullPage('calendar', $this->params()));
    }

    public function movie($slug = '')
    {
        $params = $this->params();
        $params['slug'] = $slug ?: Arr::get($params, 'slug', '');
        return $this->html($this->renderer->fullPage('movie', $params));
    }

    public function movieSources($slug = '')
    {
        $params = $this->params();
        $params['slug'] = $slug ?: Arr::get($params, 'slug', '');
        return $this->html($this->renderer->fullPage('sources', $params));
    }

    public function movieRelated($slug = '')
    {
        $params = $this->params();
        $params['slug'] = $slug ?: Arr::get($params, 'slug', '');
        return $this->html($this->renderer->fullPage('related', $params));
    }

    public function movieComments($slug = '')
    {
        $params = $this->params();
        $params['slug'] = $slug ?: Arr::get($params, 'slug', '');
        return $this->html($this->renderer->fullPage('comments', $params));
    }

    public function collections()
    {
        return $this->html($this->renderer->fullPage('collections', $this->params()));
    }

    public function collection($slug = '')
    {
        $params = $this->params();
        $params['slug'] = $slug ?: Arr::get($params, 'slug', '');
        return $this->html($this->renderer->fullPage('collection', $params));
    }

    public function shares()
    {
        return $this->html($this->renderer->fullPage('shares', $this->params()));
    }

    public function share($id = 0)
    {
        $params = $this->params();
        $params['id'] = $id ?: Arr::get($params, 'id', 0);
        return $this->html($this->renderer->fullPage('share', $params));
    }

    public function requests()
    {
        return $this->html($this->renderer->fullPage('requests', $this->params()));
    }

    public function activities()
    {
        return $this->html($this->renderer->fullPage('activities', $this->params()));
    }

    public function user($username = '')
    {
        $params = $this->params();
        $params['username'] = $username ?: Arr::get($params, 'username', '');
        return $this->html($this->renderer->fullPage('user', $params));
    }

    public function types()
    {
        return $this->html($this->renderer->fullPage('types', $this->params()));
    }

    public function genres()
    {
        return $this->html($this->renderer->fullPage('genres', $this->params()));
    }

    public function regions()
    {
        return $this->html($this->renderer->fullPage('regions', $this->params()));
    }

    public function api()
    {
        if (!Config::bool('enable_proxy', true)) {
            return $this->json(['success' => false, 'message' => 'Proxy disabled.'], 403);
        }

        $params = $this->params();
        try {
            $payload = $this->client->proxy(Arr::get($params, 'route', 'latest'), $params);
            return $this->json($payload);
        } catch (DdysException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => $e->getStatus(),
            ], $this->httpStatus($e));
        }
    }

    public function requestSubmit()
    {
        if (!Config::bool('enable_request_form', false)) {
            return $this->json(['success' => false, 'message' => '求片表单未启用。'], 403);
        }

        $input = $this->postParams();
        $honeypot = (string) Config::get('request_form.honeypot_field', 'ddys_website');
        if ($honeypot !== '' && Security::scalar(Arr::get($input, $honeypot, '')) !== '') {
            return $this->json(['success' => false, 'message' => 'Invalid request.'], 400);
        }
        if (!Security::checkNonce(Arr::get($input, 'ddys_nonce', ''), 'request')) {
            return $this->json(['success' => false, 'message' => '表单校验失败，请刷新页面后重试。'], 403);
        }
        $interval = Config::int('request_form.rate_limit_seconds', 60, 0, 3600);
        if (!$this->cache->rateLimit('request', Security::currentIp(), $interval)) {
            return $this->json(['success' => false, 'message' => '提交过于频繁，请稍后再试。'], 429);
        }

        try {
            $body = $this->client->normalizeRequestInput($input);
            $data = $this->client->createRequest($body);
            return $this->json(['success' => true, 'data' => $data]);
        } catch (DdysException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => $e->getStatus(),
            ], $this->httpStatus($e));
        }
    }

    public function check()
    {
        $config = Config::all();
        return $this->json([
            'success' => true,
            'package' => 'ddys-thinkphp-package',
            'version' => Client::VERSION,
            'routes_enabled' => Config::bool('enable_routes', true),
            'proxy_enabled' => Config::bool('enable_proxy', true),
            'request_form_enabled' => Config::bool('enable_request_form', false),
            'cache_enabled' => (bool) Arr::get($config, 'cache.enabled', true),
            'api_base_url' => Arr::get($config, 'api_base_url', Client::DEFAULT_BASE_URL),
        ]);
    }

    public function asset($type = '', $file = '')
    {
        if (!Config::bool('enable_assets_route', true)) {
            return $this->text('Not found.', 404);
        }
        $type = Security::choice($type, ['css', 'js', 'images'], '');
        $file = Security::scalar($file);
        if ($type === '' || !preg_match('/^[a-zA-Z0-9_.-]+$/', $file)) {
            return $this->text('Invalid asset.', 400);
        }
        if ($type === 'css' && substr($file, -4) !== '.css') {
            $file .= '.css';
        } elseif ($type === 'js' && substr($file, -3) !== '.js') {
            $file .= '.js';
        } elseif ($type === 'images' && !preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $file)) {
            $file .= '.png';
        }
        $path = dirname(__DIR__, 3) . '/resources/assets/' . $type . '/' . $file;
        if (!is_file($path)) {
            return $this->text('Not found.', 404);
        }
        $mime = 'application/octet-stream';
        if ($type === 'css') {
            $mime = 'text/css; charset=utf-8';
        } elseif ($type === 'js') {
            $mime = 'application/javascript; charset=utf-8';
        } elseif (preg_match('/\.png$/i', $file)) {
            $mime = 'image/png';
        }
        return Response::create(file_get_contents($path), 'html', 200)->header([
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    protected function params()
    {
        $params = [];
        if (!empty($_GET)) {
            $params = $_GET;
        } elseif (function_exists('request')) {
            $params = request()->get();
        }
        return is_array($params) ? array_map([Security::class, 'scalar'], $params) : [];
    }

    protected function postParams()
    {
        $params = [];
        if (!empty($_POST)) {
            $params = $_POST;
        } elseif (function_exists('request')) {
            $params = request()->post();
        }
        if (empty($params)) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $params = $json;
            }
        }
        return is_array($params) ? array_map([Security::class, 'scalar'], $params) : [];
    }

    protected function html($content, $status = 200)
    {
        return Response::create($content, 'html', $status)->header(['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function json(array $payload, $status = 200)
    {
        return Response::create($payload, 'json', $status)->header(['X-Content-Type-Options' => 'nosniff']);
    }

    protected function text($content, $status = 200)
    {
        return Response::create($content, 'html', $status)->header(['Content-Type' => 'text/plain; charset=utf-8']);
    }

    protected function httpStatus(DdysException $e)
    {
        $status = $e->getStatus();
        return $status >= 400 && $status <= 599 ? $status : 500;
    }
}
