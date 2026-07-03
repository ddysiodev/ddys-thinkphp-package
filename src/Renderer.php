<?php

namespace Ddys\ThinkPHP;

use Ddys\ThinkPHP\Exception\DdysException;
use Ddys\ThinkPHP\Support\Arr;
use Ddys\ThinkPHP\Support\Config;
use Ddys\ThinkPHP\Support\Security;
use Ddys\ThinkPHP\Support\Url;

class Renderer
{
    protected $client;
    protected $config;

    public function __construct(Client $client, array $config = null)
    {
        $this->client = $client;
        $this->config = $config ?: Config::all();
    }

    public function render($tag, array $params = [])
    {
        $tag = strtolower(trim((string) $tag));
        $tag = strpos($tag, 'ddys_') === 0 ? substr($tag, 5) : $tag;

        try {
            switch ($tag) {
                case 'movies':
                    return $this->listHtml($this->client->movies($params), $params);
                case 'latest':
                    return $this->listHtml($this->client->latest($params), $params);
                case 'hot':
                    return $this->listHtml($this->client->hot($params), $params);
                case 'search':
                    return $this->searchForm($params);
                case 'suggest':
                    return $this->listHtml($this->client->suggest(Arr::get($params, 'q', '')), $params);
                case 'calendar':
                    return $this->calendarHtml($this->client->calendar($params), $params);
                case 'movie':
                    return $this->detailHtml($this->client->movie(Arr::get($params, 'slug', '')), $params);
                case 'sources':
                    return $this->sourcesHtml($this->client->sources(Arr::get($params, 'slug', '')), $params);
                case 'related':
                    return $this->listHtml($this->client->related(Arr::get($params, 'slug', '')), $params);
                case 'comments':
                    return $this->listHtml($this->client->comments(Arr::get($params, 'slug', ''), $params), $params);
                case 'collections':
                    return $this->listHtml($this->client->collections($params), $params);
                case 'collection':
                    return $this->detailHtml($this->client->collection(Arr::get($params, 'slug', ''), $params), $params);
                case 'shares':
                    return $this->listHtml($this->client->shares($params), $params);
                case 'share':
                    return $this->detailHtml($this->client->share(Arr::get($params, 'id', 0)), $params);
                case 'requests':
                    return $this->listHtml($this->client->requests($params), $params);
                case 'activities':
                    return $this->listHtml($this->client->activities($params), $params);
                case 'user':
                    return $this->detailHtml($this->client->user(Arr::get($params, 'username', '')), $params);
                case 'types':
                    return $this->dictionaryHtml($this->client->types(), $params);
                case 'genres':
                    return $this->dictionaryHtml($this->client->genres(), $params);
                case 'regions':
                    return $this->dictionaryHtml($this->client->regions(), $params);
                case 'request_form':
                case 'requestform':
                    return $this->requestForm($params);
            }
        } catch (DdysException $e) {
            return $this->errorHtml($e->getMessage(), $params);
        }

        return '';
    }

    public function fullPage($view, array $params = [])
    {
        $view = Security::choice($view, ['latest', 'hot', 'search', 'calendar', 'movie', 'collections', 'requests'], 'latest');
        $title = $this->pageTitle($view);
        $assets = $this->assets();
        $nav = $this->nav($view);
        $content = $this->pageContent($view, $params);
        $template = $this->templatePath('page.php');

        ob_start();
        include $template;
        return ob_get_clean();
    }

    public function pageContent($view, array $params = [])
    {
        if ($view === 'hot') {
            return $this->render('hot', ['limit' => Arr::get($params, 'limit', 12)]);
        }
        if ($view === 'search') {
            return $this->searchForm($params);
        }
        if ($view === 'calendar') {
            return $this->render('calendar', $params);
        }
        if ($view === 'movie') {
            return $this->render('movie', ['slug' => Arr::get($params, 'slug', '')]);
        }
        if ($view === 'collections') {
            return $this->render('collections', ['page' => Arr::get($params, 'page', 1)]);
        }
        if ($view === 'requests') {
            $html = '';
            if (Config::bool('enable_request_form', false)) {
                $html .= $this->requestForm([]);
            }
            $html .= $this->render('requests', ['page' => Arr::get($params, 'page', 1)]);
            return $html;
        }
        return $this->render('latest', ['limit' => Arr::get($params, 'limit', 12)]);
    }

    public function listHtml($payload, array $params = [])
    {
        $items = $this->toList($payload);
        if (empty($items)) {
            return $this->emptyHtml('暂无低端影视内容。', $params);
        }

        $html = '<div class="ddys-thinkphp-items">';
        foreach ($items as $item) {
            $html .= $this->cardHtml($item);
        }
        $html .= '</div>';
        return $this->wrap($html, $params);
    }

    public function detailHtml($data, array $params = [])
    {
        if (!is_array($data)) {
            return $this->emptyHtml('暂无详情。', $params);
        }

        $html = '<div class="ddys-thinkphp-detail">';
        $html .= $this->cardHtml($data);
        $intro = $this->value($data, ['intro', 'description', 'summary', 'note', 'bio'], '');
        if ($intro !== '') {
            $html .= '<div class="ddys-thinkphp-description">' . nl2br(Security::h($intro)) . '</div>';
        }
        if (!empty($data['movies']) && is_array($data['movies'])) {
            $html .= '<h3>影片</h3><div class="ddys-thinkphp-items">';
            foreach ($data['movies'] as $item) {
                $html .= $this->cardHtml($item);
            }
            $html .= '</div>';
        }
        if (!empty($data['resources']) || !empty($data['sources']) || !empty($data['online']) || !empty($data['download'])) {
            $html .= $this->sourcesHtml($data, $params, true);
        }
        $html .= '</div>';
        return $this->wrap($html, $params);
    }

    public function sourcesHtml($data, array $params = [], $inner = false)
    {
        $groups = [];
        if (is_array($data)) {
            if (isset($data['resources'])) {
                $groups['资源'] = $data['resources'];
            } elseif (isset($data['sources'])) {
                $groups['资源'] = $data['sources'];
            } elseif (isset($data['online']) || isset($data['download'])) {
                if (isset($data['online'])) {
                    $groups['在线播放'] = $data['online'];
                }
                if (isset($data['download'])) {
                    $groups['下载资源'] = $data['download'];
                }
            } else {
                $groups = $this->isAssoc($data) ? $data : ['资源' => $data];
            }
        }

        $html = '<div class="ddys-thinkphp-sources">';
        foreach ($groups as $name => $resources) {
            if (!is_array($resources)) {
                continue;
            }
            $html .= '<section class="ddys-thinkphp-source-group"><h3>' . Security::h($name) . '</h3>';
            foreach ($resources as $resource) {
                if (!is_array($resource)) {
                    continue;
                }
                $title = $this->value($resource, ['title', 'name', 'download_type', 'type', 'quality'], '资源');
                $url = $this->value($resource, ['url', 'link', 'href'], '');
                $safe = preg_match('#^(https?:|magnet:|ed2k:|thunder:)#i', $url) ? $url : '';
                $html .= '<p class="ddys-thinkphp-resource">';
                $html .= $safe !== '' ? '<a href="' . Security::attr($safe) . '" target="_blank" rel="noopener">' . Security::h($title) . '</a>' : Security::h($title);
                $html .= '</p>';
            }
            $html .= '</section>';
        }
        $html .= '</div>';
        return $inner ? $html : $this->wrap($html, $params);
    }

    public function calendarHtml($data, array $params = [])
    {
        $days = is_array($data) && isset($data['days']) ? $data['days'] : $data;
        if (!is_array($days)) {
            return $this->listHtml($data, $params);
        }

        $html = '<div class="ddys-thinkphp-calendar">';
        foreach ($days as $day => $dayData) {
            $label = (string) $day;
            $items = $dayData;
            if (is_array($dayData) && (isset($dayData['shows']) || isset($dayData['day']) || isset($dayData['weekday']))) {
                $parts = [];
                if (!empty($dayData['day'])) {
                    $parts[] = (string) $dayData['day'] . '日';
                }
                if (!empty($dayData['weekday'])) {
                    $parts[] = (string) $dayData['weekday'];
                }
                $label = empty($parts) ? $label : implode(' ', $parts);
                $items = isset($dayData['shows']) && is_array($dayData['shows']) ? $dayData['shows'] : [];
            }
            $html .= '<section class="ddys-thinkphp-calendar-day"><h3>' . Security::h($label) . '</h3>';
            if (is_array($items) && !empty($items)) {
                $html .= '<div class="ddys-thinkphp-items">';
                foreach ($items as $item) {
                    $html .= $this->cardHtml($item);
                }
                $html .= '</div>';
            } else {
                $html .= '<p class="ddys-thinkphp-empty-inline">暂无更新。</p>';
            }
            $html .= '</section>';
        }
        $html .= '</div>';
        return $this->wrap($html, $params);
    }

    public function dictionaryHtml($items, array $params = [])
    {
        $items = $this->toList($items);
        if (empty($items)) {
            return $this->emptyHtml('暂无字典数据。', $params);
        }
        $html = '<div class="ddys-thinkphp-tags">';
        foreach ($items as $item) {
            $label = is_array($item) ? $this->value($item, ['name', 'title', 'label', 'value'], '') : $item;
            if ($label !== '') {
                $html .= '<span>' . Security::h($label) . '</span>';
            }
        }
        $html .= '</div>';
        return $this->wrap($html, $params);
    }

    public function searchForm(array $params = [])
    {
        $q = Security::scalar(Arr::get($params, 'q', $this->requestParam('q', $this->requestParam('ddys_q', ''))));
        $type = Security::choice(Arr::get($params, 'type', $this->requestParam('type', $this->requestParam('ddys_type', 'movie'))), ['movie', 'share', 'request'], 'movie');
        $html = '<form class="ddys-thinkphp-search" method="get" action="' . Security::attr(Url::page('search')) . '">';
        $html .= '<input type="search" name="q" value="' . Security::attr($q) . '" placeholder="搜索低端影视" />';
        $html .= '<select name="type"><option value="movie"' . ($type === 'movie' ? ' selected' : '') . '>影片</option><option value="share"' . ($type === 'share' ? ' selected' : '') . '>分享</option><option value="request"' . ($type === 'request' ? ' selected' : '') . '>求片</option></select>';
        $html .= '<button type="submit">搜索</button></form>';
        if ($q !== '') {
            try {
                $html .= $this->listHtml($this->client->search(['q' => $q, 'type' => $type, 'per_page' => Arr::get($params, 'per_page', 12)]), $params);
            } catch (DdysException $e) {
                $html .= $this->errorHtml($e->getMessage(), $params);
            }
        }
        return $this->wrap($html, $params);
    }

    public function requestForm(array $params = [])
    {
        if (!Config::bool('enable_request_form', false)) {
            return $this->emptyHtml('求片表单未启用。', $params);
        }
        $honeypot = (string) Config::get('request_form.honeypot_field', 'ddys_website');
        $html = '<form class="ddys-thinkphp-request-form" method="post" action="' . Security::attr(Url::route('request-submit')) . '" data-ddys-thinkphp-request-form>';
        $html .= '<input type="hidden" name="ddys_nonce" value="' . Security::attr(Security::nonce('request')) . '" />';
        $html .= '<input class="ddys-thinkphp-hp" type="text" name="' . Security::attr($honeypot) . '" value="" tabindex="-1" autocomplete="off" />';
        $html .= '<label>片名<input type="text" name="title" maxlength="255" required /></label>';
        $html .= '<label>年份<input type="number" name="year" min="1900" max="2099" /></label>';
        $html .= '<label>类型<select name="type"><option value=""></option><option value="movie">电影</option><option value="series">剧集</option><option value="variety">综艺</option><option value="anime">动漫</option></select></label>';
        $html .= '<label>豆瓣 ID<input type="text" name="douban_id" maxlength="30" /></label>';
        $html .= '<label>备注<textarea name="description" maxlength="1000"></textarea></label>';
        $html .= '<button type="submit">提交求片</button><p class="ddys-thinkphp-status" role="status"></p></form>';
        return $this->wrap($html, $params);
    }

    public function assets()
    {
        if (!Config::bool('display.load_assets', true)) {
            return '';
        }
        $version = Client::VERSION;
        return '<link rel="stylesheet" href="' . Security::attr(Url::asset('css', 'frontend.css?v=' . $version)) . '" />'
            . '<script defer src="' . Security::attr(Url::asset('js', 'frontend.js?v=' . $version)) . '"></script>';
    }

    public function nav($active = 'latest')
    {
        if (!Config::bool('display.show_nav', true)) {
            return '';
        }
        $items = [
            'latest' => '最新',
            'hot' => '热门',
            'search' => '搜索',
            'calendar' => '日历',
            'collections' => '片单',
            'requests' => '求片',
        ];
        $html = '<nav class="ddys-thinkphp-nav">';
        foreach ($items as $view => $label) {
            $class = $view === $active ? ' class="is-active"' : '';
            $html .= '<a' . $class . ' href="' . Security::attr(Url::page($view)) . '">' . Security::h($label) . '</a>';
        }
        $html .= '</nav>';
        return $html;
    }

    protected function cardHtml($item)
    {
        if (!is_array($item)) {
            return '';
        }
        $settings = (array) Arr::get($this->config, 'display', []);
        $title = $this->value($item, ['title', 'name', 'cn_name', 'en_name', 'username', 'search_keyword'], 'Untitled');
        $poster = Security::safeMediaUrl($this->value($item, ['poster', 'cover', 'avatar'], ''));
        $url = $this->siteUrl($item);
        $target = Security::choice(Arr::get($settings, 'target', '_blank'), ['_blank', '_self'], '_blank');
        $meta = [];
        foreach (['year', 'type', 'type_code', 'region', 'quality', 'episode', 'status', 'resource_type'] as $key) {
            if (!empty($item[$key])) {
                $meta[] = is_array($item[$key]) ? implode(', ', $item[$key]) : $item[$key];
            }
        }
        if (!empty($item['rating'])) {
            $meta[] = '评分 ' . $item['rating'];
        }
        if (!empty($item['is_premiere'])) {
            $meta[] = '首播';
        }
        if (!empty($item['is_finale'])) {
            $meta[] = '季终';
        }
        $summary = $this->value($item, ['description', 'intro', 'summary', 'note', 'content', 'bio'], '');

        $html = '<article class="ddys-thinkphp-card">';
        if ($poster !== '') {
            $html .= '<div class="ddys-thinkphp-poster"><img src="' . Security::attr($poster) . '" alt="' . Security::attr($title) . '" loading="lazy" /></div>';
        }
        $html .= '<div class="ddys-thinkphp-card-body"><h3 class="ddys-thinkphp-title">';
        if ($url !== '' && !empty($settings['show_source_link'])) {
            $html .= '<a href="' . Security::attr($url) . '" target="' . Security::attr($target) . '" rel="noopener">' . Security::h($title) . '</a>';
        } else {
            $html .= Security::h($title);
        }
        $html .= '</h3>';
        if (!empty($meta)) {
            $html .= '<div class="ddys-thinkphp-meta">' . Security::h(implode(' / ', $meta)) . '</div>';
        }
        if ($summary !== '') {
            $html .= '<div class="ddys-thinkphp-summary">' . Security::h(Security::substr(strip_tags((string) $summary), 0, 160)) . '</div>';
        }
        $html .= '</div></article>';
        return $html;
    }

    protected function wrap($html, array $params = [])
    {
        $settings = (array) Arr::get($this->config, 'display', []);
        $layout = Security::choice(Arr::get($params, 'layout', Arr::get($settings, 'layout', 'grid')), ['grid', 'list', 'compact'], 'grid');
        $theme = Security::choice(Arr::get($params, 'theme', Arr::get($settings, 'theme', 'auto')), ['auto', 'light', 'dark'], 'auto');
        $columns = Security::intRange(Arr::get($params, 'columns', Arr::get($settings, 'columns', 4)), 4, 1, 6);
        return '<div class="ddys-thinkphp ddys-thinkphp-theme-' . Security::attr($theme) . ' ddys-thinkphp-layout-' . Security::attr($layout) . '" style="--ddys-thinkphp-columns:' . $columns . '">' . $html . '</div>';
    }

    protected function errorHtml($message, array $params = [])
    {
        return $this->wrap('<div class="ddys-thinkphp-error">' . Security::h($message) . '</div>', $params);
    }

    protected function emptyHtml($message, array $params = [])
    {
        return $this->wrap('<div class="ddys-thinkphp-empty">' . Security::h($message) . '</div>', $params);
    }

    protected function toList($data)
    {
        if (!is_array($data)) {
            return [];
        }
        foreach (['data', 'items', 'movies', 'results', 'related', 'series', 'shares', 'requests', 'activities', 'comments'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }
        if ($this->isAssoc($data)) {
            return [$data];
        }
        return $data;
    }

    protected function isAssoc($array)
    {
        return is_array($array) && !empty($array) && array_keys($array) !== range(0, count($array) - 1);
    }

    protected function value(array $item, array $keys, $fallback = '')
    {
        foreach ($keys as $key) {
            if (isset($item[$key]) && $item[$key] !== '') {
                return is_array($item[$key]) ? implode(', ', $item[$key]) : $item[$key];
            }
        }
        return $fallback;
    }

    protected function siteUrl(array $item)
    {
        $site = rtrim((string) Arr::get($this->config, 'site_base_url', 'https://ddys.io'), '/');
        if (isset($item['url']) && preg_match('#^https?://#i', $item['url'])) {
            return $item['url'];
        }
        if (isset($item['url']) && substr($item['url'], 0, 1) === '/') {
            return $site . $item['url'];
        }
        if (isset($item['slug']) && $item['slug'] !== '') {
            return $site . '/movie/' . rawurlencode($item['slug']);
        }
        return '';
    }

    protected function requestParam($key, $default = '')
    {
        if (isset($_GET[$key])) {
            return Security::scalar($_GET[$key], $default);
        }
        if (function_exists('request')) {
            $value = request()->param($key, $default);
            return Security::scalar($value, $default);
        }
        return $default;
    }

    protected function pageTitle($view)
    {
        $titles = [
            'latest' => '低端影视',
            'hot' => '热门影片',
            'search' => '搜索',
            'calendar' => '日历',
            'movie' => '影片详情',
            'collections' => '片单',
            'requests' => '求片',
        ];
        return isset($titles[$view]) ? $titles[$view] : '低端影视';
    }

    protected function templatePath($file)
    {
        $custom = trim((string) Config::get('display.view_path', ''));
        if ($custom !== '') {
            $path = rtrim($custom, '/\\') . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                return $path;
            }
        }
        return dirname(__DIR__) . '/resources/views/' . $file;
    }
}
