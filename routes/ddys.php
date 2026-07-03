<?php

use Ddys\ThinkPHP\Http\Controller\DdysController;
use Ddys\ThinkPHP\Support\Config;
use think\facade\Route;

if (!Config::bool('enable_routes', true)) {
    return;
}

$prefix = trim((string) Config::get('route_prefix', 'ddys'), '/');
$middleware = Config::get('route_middleware', []);
$middleware = is_array($middleware) ? $middleware : [];

$group = Route::group($prefix, function () {
    Route::get('', DdysController::class . '@index');
    Route::get('/', DdysController::class . '@index');
    Route::get('hot', DdysController::class . '@hot');
    Route::get('search', DdysController::class . '@search');
    Route::get('calendar', DdysController::class . '@calendar');
    Route::get('movie/:slug', DdysController::class . '@movie');
    Route::get('collections', DdysController::class . '@collections');
    Route::get('requests', DdysController::class . '@requests');
    Route::get('api', DdysController::class . '@api');
    Route::post('request-submit', DdysController::class . '@requestSubmit');
    Route::get('check', DdysController::class . '@check');
    Route::get('assets/:type/:file', DdysController::class . '@asset');
});

if (!empty($middleware) && method_exists($group, 'middleware')) {
    $group->middleware($middleware);
}
