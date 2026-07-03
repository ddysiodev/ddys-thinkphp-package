<?php

namespace Ddys\ThinkPHP;

use Ddys\ThinkPHP\Cache\Repository as CacheRepository;
use Ddys\ThinkPHP\Command\ClearCacheCommand;
use Ddys\ThinkPHP\Command\PublishAssetsCommand;
use Ddys\ThinkPHP\Command\RoutesCommand;
use Ddys\ThinkPHP\Command\TestCommand;
use Ddys\ThinkPHP\Support\Config;
use think\Service;

class DdysService extends Service
{
    public function register()
    {
        $app = $this->app;
        $clientFactory = function () use ($app) {
            return new Client(Config::all(), new CacheRepository($app));
        };

        $this->app->bind(CacheRepository::class, function () use ($app) {
            return new CacheRepository($app);
        });

        $this->app->bind(Client::class, $clientFactory);
        $this->app->bind('ddys', $clientFactory);
    }

    public function boot()
    {
        if (Config::bool('enable_routes', true)) {
            $this->loadRoutesFrom(dirname(__DIR__) . '/routes/ddys.php');
        }

        $this->commands([
            TestCommand::class,
            ClearCacheCommand::class,
            RoutesCommand::class,
            PublishAssetsCommand::class,
        ]);
    }
}
