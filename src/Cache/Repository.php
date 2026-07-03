<?php

namespace Ddys\ThinkPHP\Cache;

use Ddys\ThinkPHP\Support\Arr;
use Ddys\ThinkPHP\Support\Config;
use Throwable;

class Repository
{
    protected $app;
    protected $config;

    public function __construct($app = null, array $config = null)
    {
        $this->app = $app;
        $this->config = $config ?: Config::all();
    }

    public function get($key)
    {
        if (!$this->enabled()) {
            return null;
        }

        try {
            $value = $this->store()->get($this->key($key));
            return $value === false ? null : $value;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function set($key, $value, $ttl)
    {
        if (!$this->enabled() || (int) $ttl <= 0) {
            return false;
        }

        try {
            $fullKey = $this->key($key);
            $this->store()->set($fullKey, $value, (int) $ttl);
            $this->rememberKey($fullKey);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function delete($key)
    {
        try {
            return (bool) $this->store()->delete($this->key($key));
        } catch (Throwable $e) {
            return false;
        }
    }

    public function clear()
    {
        $count = 0;
        foreach ($this->knownKeys() as $key) {
            try {
                if ($this->store()->delete($key)) {
                    $count++;
                }
            } catch (Throwable $e) {
                // Keep clearing the rest of the package keys.
            }
        }
        try {
            $this->store()->delete($this->indexKey());
        } catch (Throwable $e) {
            // Ignore cache backend failures.
        }
        return $count;
    }

    public function rateLimit($scope, $identifier, $seconds)
    {
        $seconds = (int) $seconds;
        if ($seconds <= 0) {
            return true;
        }
        $key = 'rate:' . md5($scope . '|' . $identifier);
        if ($this->get($key)) {
            return false;
        }
        $this->set($key, time(), $seconds);
        return true;
    }

    public function ttlForPath($path)
    {
        $path = '/' . ltrim((string) $path, '/');
        if (preg_match('#^/(types|genres|regions|calendar)$#', $path)) {
            return (int) Arr::get($this->config, 'cache.dictionary_ttl', 86400);
        }
        if (preg_match('#^/(latest|hot)$#', $path)) {
            return (int) Arr::get($this->config, 'cache.fresh_ttl', 300);
        }
        if (preg_match('#^/(movies/[^/]+|movies/[^/]+/sources|movies/[^/]+/related|collections/[^/]+|shares/[0-9]+)$#', $path)) {
            return (int) Arr::get($this->config, 'cache.detail_ttl', 1800);
        }
        if (preg_match('#^/(movies/[^/]+/comments|suggest|shares|requests|activities|user/)#', $path)) {
            return (int) Arr::get($this->config, 'cache.community_ttl', 120);
        }
        if (preg_match('#^/(movies|search|collections)#', $path)) {
            return (int) Arr::get($this->config, 'cache.list_ttl', 600);
        }
        return (int) Arr::get($this->config, 'cache.default_ttl', 300);
    }

    protected function enabled()
    {
        return (bool) Arr::get($this->config, 'cache.enabled', true) && class_exists('think\\facade\\Cache');
    }

    protected function store()
    {
        $store = Arr::get($this->config, 'cache.store');
        if ($store) {
            return \think\facade\Cache::store($store);
        }
        return \think\facade\Cache::store();
    }

    protected function key($key)
    {
        return $this->prefix() . preg_replace('/[^a-zA-Z0-9:_-]/', '', (string) $key);
    }

    protected function prefix()
    {
        return (string) Arr::get($this->config, 'cache.prefix', 'ddys:thinkphp:');
    }

    protected function indexKey()
    {
        return $this->prefix() . 'index';
    }

    protected function knownKeys()
    {
        try {
            $keys = $this->store()->get($this->indexKey(), []);
        } catch (Throwable $e) {
            return [];
        }
        return is_array($keys) ? array_values(array_unique($keys)) : [];
    }

    protected function rememberKey($key)
    {
        $keys = $this->knownKeys();
        $keys[] = $key;
        $keys = array_values(array_unique($keys));
        try {
            $this->store()->set($this->indexKey(), $keys, 604800);
        } catch (Throwable $e) {
            // The cache value itself has already been written.
        }
    }
}
