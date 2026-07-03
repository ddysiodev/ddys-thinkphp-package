# 低端影视 ThinkPHP 扩展包

中文 | [English](README.md)

[低端影视](https://ddys.io/) API 的官方 ThinkPHP 扩展包，用于在 ThinkPHP 6.1 / 8 项目中集成低端影视内容展示、本地 JSON 代理、服务端求片、缓存、命令行诊断和开发者调用接口。

- GitHub 仓库：[ddysiodev/ddys-thinkphp-package](https://github.com/ddysiodev/ddys-thinkphp-package)
- GitHub Release：[v0.1.0](https://github.com/ddysiodev/ddys-thinkphp-package/releases/tag/v0.1.0)
- 下载压缩包：[ddys-thinkphp-package-v0.1.0.zip](https://github.com/ddysiodev/ddys-thinkphp-package/releases/download/v0.1.0/ddys-thinkphp-package-v0.1.0.zip)
- Composer 包名：`ddysiodev/ddys-thinkphp-package`
- 推荐环境：ThinkPHP 6.1 / 8，PHP 7.2.5+，UTF-8 项目
- 核心入口：`Ddys\ThinkPHP\Client`、`Ddys\ThinkPHP\Facade\Ddys`、`ddys_client()`、`ddys_render()`

## 功能

- ThinkPHP 服务自动发现：通过 `extra.think.services` 注册 `DdysService`。
- 配置发布：通过 `extra.think.config` 发布 `config/ddys.php`。
- 全功能 API Client：影片、最新、热门、搜索、建议、日历、字典、片单、分享、求片、动态、用户、评论、举报、关注。
- 本地 JSON 代理：前端请求本站 `/ddys/api`，服务端再请求低端影视 API。
- 服务端求片提交：API Key 只保存在服务端，支持 nonce 校验、蜜罐字段和 IP 限流。
- 前台独立页面：最新、热门、搜索、日历、影片详情、片单、求片。
- 内置渲染器：可直接输出卡片列表、详情、资源、日历、字典、搜索框和求片表单。
- 模板助手函数：`ddys_latest()`、`ddys_hot()`、`ddys_movie()`、`ddys_render()` 等。
- Facade 调用：`Ddys::latest()`、`Ddys::movie('i-robot')`。
- ThinkPHP Cache 集成：按接口类型设置 TTL，支持项目已有缓存驱动。
- 命令行：连接测试、清理缓存、发布静态资源、查看路由。
- 静态资源：前台 CSS/JS 和图标来自主站图标集。
- 伪静态说明：提供 Apache、Nginx、IIS 示例。

## 安装

```bash
composer require ddysiodev/ddys-thinkphp-package
```

ThinkPHP 的 `service:discover` 和 `vendor:publish` 通常会在 Composer 安装后自动执行。也可以手动执行：

```bash
php think service:discover
php think vendor:publish
```

发布后检查配置：

```php
// config/ddys.php
return [
    'api_base_url' => 'https://ddys.io/api/v1',
    'site_base_url' => 'https://ddys.io',
    'api_key' => env('DDYS_API_KEY', ''),
    'route_prefix' => 'ddys',
    'enable_proxy' => true,
    'enable_request_form' => false,
];
```

## 快速开始

访问：

```text
/ddys
/ddys/hot
/ddys/search
/ddys/calendar
/ddys/movie/i-robot
/ddys/collections
/ddys/requests
```

在模板或控制器中调用：

```php
echo ddys_render('latest', ['limit' => 12]);

$movie = ddys_movie('i-robot');
$latest = ddys_latest(['limit' => 6]);
```

Facade：

```php
use Ddys\ThinkPHP\Facade\Ddys;

$latest = Ddys::latest(['limit' => 12]);
$movie = Ddys::movie('i-robot');
$sources = Ddys::sources('i-robot');
```

依赖注入：

```php
use Ddys\ThinkPHP\Client;

class MovieController
{
    public function index(Client $ddys)
    {
        return json($ddys->hot(['limit' => 10]));
    }
}
```

## 配置

常用配置：

```php
'api_base_url' => 'https://ddys.io/api/v1',
'site_base_url' => 'https://ddys.io',
'api_key' => env('DDYS_API_KEY', ''),
'timeout' => 12,
'route_prefix' => 'ddys',
'enable_routes' => true,
'enable_proxy' => true,
'enable_request_form' => false,
'enable_assets_route' => true,
```

缓存配置：

```php
'cache' => [
    'enabled' => true,
    'store' => null,
    'prefix' => 'ddys:thinkphp:',
    'default_ttl' => 300,
    'dictionary_ttl' => 86400,
    'fresh_ttl' => 300,
    'list_ttl' => 600,
    'detail_ttl' => 1800,
    'community_ttl' => 120,
],
```

展示配置：

```php
'display' => [
    'theme' => 'auto',
    'layout' => 'grid',
    'columns' => 4,
    'target' => '_blank',
    'show_source_link' => true,
    'show_nav' => true,
    'load_assets' => true,
    'view_path' => '',
    'asset_url' => '',
],
```

如果执行 `php think ddys:publish-assets`，可以把 `asset_url` 改成：

```php
'asset_url' => '/static/ddys-thinkphp',
```

## 路由

默认 `route_prefix` 是 `ddys`：

```text
GET  /ddys
GET  /ddys/hot
GET  /ddys/search
GET  /ddys/calendar
GET  /ddys/movie/:slug
GET  /ddys/collections
GET  /ddys/requests
GET  /ddys/api
POST /ddys/request-submit
GET  /ddys/check
GET  /ddys/assets/:type/:file
```

本地 JSON 代理示例：

```text
/ddys/api?route=latest&limit=12
/ddys/api?route=hot&limit=10
/ddys/api?route=search&q=星际&type=movie
/ddys/api?route=calendar&year=2026&month=7
/ddys/api?route=movie&slug=i-robot
/ddys/api?route=sources&slug=i-robot
/ddys/api?route=related&slug=i-robot
/ddys/api?route=collections&per_page=10
/ddys/api?route=requests&per_page=10
```

代理只允许配置中的 `security.proxy_allow_routes`，不会把任意 URL 暴露给前端。

## API Client 方法

读取接口：

```php
$ddys = ddys_client();

$ddys->movies(['type' => 'movie', 'page' => 1, 'per_page' => 24]);
$ddys->latest(['limit' => 12]);
$ddys->hot(['limit' => 10]);
$ddys->search(['q' => '星际', 'type' => 'movie', 'per_page' => 10]);
$ddys->suggest('星际');
$ddys->calendar(['year' => 2026, 'month' => 7]);
$ddys->movie('i-robot');
$ddys->sources('i-robot');
$ddys->related('i-robot');
$ddys->comments('i-robot', ['per_page' => 20]);
$ddys->types();
$ddys->genres();
$ddys->regions();
$ddys->collections(['per_page' => 10]);
$ddys->collection('best-sci-fi');
$ddys->shares(['per_page' => 10]);
$ddys->share(1);
$ddys->requests(['per_page' => 10]);
$ddys->activities(['type' => 'share']);
$ddys->user('demo');
```

需要 API Key 的接口：

```php
$ddys->createRequest([
    'title' => 'Dune 2',
    'year' => 2024,
    'type' => 'movie',
    'douban_id' => '35652650',
]);

$ddys->createComment([
    'target_type' => 'movie',
    'target_id' => 1580,
    'content' => '好片',
]);

$ddys->deleteComment(12345);
$ddys->reportInvalidResource(['movie_id' => 1580, 'resource_id' => 31422]);
$ddys->follow('demo');
$ddys->unfollow('demo');
$ddys->me();
```

## 模板渲染

```php
echo ddys_render('movies', ['type' => 'movie', 'per_page' => 24]);
echo ddys_render('latest', ['limit' => 12]);
echo ddys_render('hot', ['limit' => 10]);
echo ddys_render('search');
echo ddys_render('calendar', ['year' => 2026, 'month' => 7]);
echo ddys_render('movie', ['slug' => 'i-robot']);
echo ddys_render('sources', ['slug' => 'i-robot']);
echo ddys_render('related', ['slug' => 'i-robot']);
echo ddys_render('collections', ['per_page' => 10]);
echo ddys_render('requests', ['per_page' => 10]);
echo ddys_render('types');
echo ddys_request_form();
```

## 命令行

```bash
php think ddys:test
php think ddys:clear-cache
php think ddys:publish-assets
php think ddys:routes
```

## 伪静态

ThinkPHP 自身会接管路由。若服务器还没有把请求交给 `index.php`，可参考：

### Apache

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
```

### Nginx

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?s=$1 last;
        break;
    }
}
```

### IIS

```xml
<rule name="ThinkPHP Routes" stopProcessing="true">
  <match url="^(.*)$" />
  <conditions>
    <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
    <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
  </conditions>
  <action type="Rewrite" url="index.php/{R:1}" appendQueryString="true" />
</rule>
```

## 安全建议

- 把 `DDYS_API_KEY` 放在服务器环境变量或 ThinkPHP `.env` 中。
- 前端只使用 `/ddys/api` 读取公开数据，不要在浏览器暴露 API Key。
- 求片表单默认关闭，需要时开启 `enable_request_form`。
- 如果站点有登录体系，可以给 `route_middleware` 添加自己的中间件。
- 生产环境建议开启 ThinkPHP 缓存驱动，降低 API 请求压力。

## 本地检查

```bash
node tools/check.mjs
node --test tests/*.test.mjs
```

检查覆盖 Composer 元数据、服务自动发现、配置发布、路由、Client 方法、渲染器、命令、静态资源、README 文案和敏感信息。
