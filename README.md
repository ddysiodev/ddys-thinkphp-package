# DDYS ThinkPHP Package

[中文](README.zh-CN.md) | English

Official ThinkPHP package for the [DDYS](https://ddys.io/) API. It adds DDYS content pages, a local JSON proxy, server-side request submission, caching, diagnostics, commands, helpers, and a full PHP client to ThinkPHP 6.1 / 8 projects.

- Repository: [ddysiodev/ddys-thinkphp-package](https://github.com/ddysiodev/ddys-thinkphp-package)
- GitHub Release: [v0.1.1](https://github.com/ddysiodev/ddys-thinkphp-package/releases/tag/v0.1.1)
- Download ZIP: [ddys-thinkphp-package-v0.1.1.zip](https://github.com/ddysiodev/ddys-thinkphp-package/releases/download/v0.1.1/ddys-thinkphp-package-v0.1.1.zip)
- Composer package: `ddysiodev/ddys-thinkphp-package`
- Recommended environment: ThinkPHP 6.1 / 8, PHP 7.2.5+, UTF-8 project
- Main entry points: `Ddys\ThinkPHP\Client`, `Ddys\ThinkPHP\Facade\Ddys`, `ddys_client()`, `ddys_render()`

## Features

- ThinkPHP service discovery through `extra.think.services`.
- Config publishing through `extra.think.config`.
- Full API client for movies, latest, hot, search, suggestions, calendar, dictionaries, collections, shares, requests, activities, users, comments, reports, and follow actions.
- Local JSON proxy through `/ddys/api`.
- Server-side request form with nonce checks, honeypot field, and IP rate limiting.
- Standalone frontend pages for latest, movies, hot, search, calendar, movie detail, sources, related movies, comments, collections, shares, requests, activities, users, and dictionaries.
- Built-in renderer for lists, details, sources, calendar, dictionaries, search, and request form.
- Helper functions and Facade calls.
- ThinkPHP Cache integration with route-aware TTLs.
- Commands for API testing, cache clearing, asset publishing, and route listing.
- Apache, Nginx, and IIS rewrite examples.

## Installation

```bash
composer require ddysiodev/ddys-thinkphp-package
```

ThinkPHP usually runs service discovery and vendor publish after Composer install. You can also run them manually:

```bash
php think service:discover
php think vendor:publish
```

Then review `config/ddys.php`.

## Quick Start

Visit:

```text
/ddys
/ddys/hot
/ddys/search
/ddys/calendar
/ddys/movie/i-robot
/ddys/collections
/ddys/requests
```

Use helpers:

```php
echo ddys_render('latest', ['limit' => 12]);

$movie = ddys_movie('i-robot');
$latest = ddys_latest(['limit' => 6]);
```

Use the Facade:

```php
use Ddys\ThinkPHP\Facade\Ddys;

$latest = Ddys::latest(['limit' => 12]);
$movie = Ddys::movie('i-robot');
$sources = Ddys::sources('i-robot');
```

Use dependency injection:

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

## Routes

The default `route_prefix` is `ddys`:

```text
GET  /ddys
GET  /ddys/movies
GET  /ddys/hot
GET  /ddys/search
GET  /ddys/calendar
GET  /ddys/movie/:slug
GET  /ddys/movie/:slug/sources
GET  /ddys/movie/:slug/related
GET  /ddys/movie/:slug/comments
GET  /ddys/collections
GET  /ddys/collection/:slug
GET  /ddys/shares
GET  /ddys/share/:id
GET  /ddys/requests
GET  /ddys/activities
GET  /ddys/user/:username
GET  /ddys/types
GET  /ddys/genres
GET  /ddys/regions
GET  /ddys/api
POST /ddys/request-submit
GET  /ddys/check
GET  /ddys/assets/:type/:file
```

Local JSON proxy examples:

```text
/ddys/api?route=latest&limit=12
/ddys/api?route=hot&limit=10
/ddys/api?route=search&q=interstellar&type=movie
/ddys/api?route=calendar&year=2026&month=7
/ddys/api?route=movie&slug=i-robot
/ddys/api?route=sources&slug=i-robot
/ddys/api?route=related&slug=i-robot
/ddys/api?route=comments&slug=i-robot
/ddys/api?route=collections&per_page=10
/ddys/api?route=shares&per_page=10
/ddys/api?route=requests&per_page=10
/ddys/api?route=activities&per_page=10
/ddys/api?route=types
```

## Client Methods

```php
$ddys = ddys_client();

$ddys->movies(['type' => 'movie', 'page' => 1, 'per_page' => 24]);
$ddys->latest(['limit' => 12]);
$ddys->hot(['limit' => 10]);
$ddys->search(['q' => 'interstellar', 'type' => 'movie']);
$ddys->suggest('interstellar');
$ddys->calendar(['year' => 2026, 'month' => 7]);
$ddys->movie('i-robot');
$ddys->sources('i-robot');
$ddys->related('i-robot');
$ddys->comments('i-robot');
$ddys->types();
$ddys->genres();
$ddys->regions();
$ddys->collections();
$ddys->collection('best-sci-fi');
$ddys->shares();
$ddys->share(1);
$ddys->requests();
$ddys->activities(['type' => 'share']);
$ddys->user('demo');
```

Authenticated methods:

```php
$ddys->createRequest(['title' => 'Dune 2', 'year' => 2024, 'type' => 'movie']);
$ddys->createComment(['target_type' => 'movie', 'target_id' => 1580, 'content' => 'Nice movie']);
$ddys->deleteComment(12345);
$ddys->reportInvalidResource(['movie_id' => 1580, 'resource_id' => 31422]);
$ddys->follow('demo');
$ddys->unfollow('demo');
$ddys->me();
```

## Rendering

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

## Commands

```bash
php think ddys:test
php think ddys:clear-cache
php think ddys:publish-assets
php think ddys:routes
```

## Rewrite Examples

ThinkPHP handles the package routes after the web server passes requests to `index.php`.

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

## Local Checks

```bash
node tools/check.mjs
node --test tests/*.test.mjs
```

Checks cover Composer metadata, service discovery, config publishing, routes, client methods, renderer behavior, commands, assets, README wording, and sensitive text.
