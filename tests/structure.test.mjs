import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('Composer metadata enables ThinkPHP service discovery and config publishing', () => {
  const composer = JSON.parse(read('composer.json'));

  assert.equal(composer.name, 'ddysiodev/ddys-thinkphp-package');
  assert.equal(composer.description, '低端影视 API 的官方 ThinkPHP 扩展包。');
  assert.equal(composer.autoload['psr-4']['Ddys\\ThinkPHP\\'], 'src/');
  assert.deepEqual(composer.autoload.files, ['src/helper.php']);
  assert.deepEqual(composer.extra.think.services, ['Ddys\\ThinkPHP\\DdysService']);
  assert.equal(composer.extra.think.config.ddys, 'config/ddys.php');
});

test('routes expose standalone pages, local JSON proxy, request submit, checks, and assets', () => {
  const routes = read('routes/ddys.php');

  for (const route of [
    "Route::get('',",
    "Route::get('hot'",
    "Route::get('search'",
    "Route::get('calendar'",
    "Route::get('movie/:slug'",
    "Route::get('collections'",
    "Route::get('requests'",
    "Route::get('api'",
    "Route::post('request-submit'",
    "Route::get('check'",
    "Route::get('assets/:type/:file'",
  ]) {
    assert.match(routes, new RegExp(route.replaceAll('(', '\\(')));
  }
});

test('client covers public, community, authenticated, and proxy API operations', () => {
  const client = read('src/Client.php');
  const methods = [
    'movies', 'latest', 'hot', 'search', 'suggest', 'calendar', 'movie',
    'sources', 'related', 'comments', 'types', 'genres', 'regions',
    'collections', 'collection', 'shares', 'share', 'requests',
    'createRequest', 'activities', 'user', 'me', 'createComment',
    'deleteComment', 'reportInvalidResource', 'follow', 'unfollow',
    'proxy', 'normalizeRequestInput',
  ];

  for (const method of methods) {
    assert.match(client, new RegExp(`function\\s+${method}\\s*\\(`));
  }

  assert.match(client, /proxy_allow_routes/);
  assert.match(client, /Authorization: Bearer/);
  assert.match(client, /curl_init/);
  assert.match(client, /file_get_contents/);
});

test('renderer handles real API data shapes and expected public views', () => {
  const renderer = read('src/Renderer.php');

  for (const token of [
    'latest', 'hot', 'search', 'calendar', 'movie', 'collections', 'requests',
    'shows', 'cn_name', 'en_name', 'episode', 'is_premiere', 'is_finale',
    'resources', 'online', 'download', 'requestForm', 'dictionaryHtml',
  ]) {
    assert.match(renderer, new RegExp(token));
  }

  assert.match(renderer, /isset\(\$data\[\$key\]\) && is_array\(\$data\[\$key\]\)/);
});

test('commands and helper functions are registered for developers', () => {
  const service = read('src/DdysService.php');
  const helpers = read('src/helper.php');

  for (const klass of ['TestCommand', 'ClearCacheCommand', 'RoutesCommand', 'PublishAssetsCommand']) {
    assert.match(service, new RegExp(`${klass}::class`));
  }
  for (const fn of [
    'ddys_client', 'ddys_render', 'ddys_latest', 'ddys_hot', 'ddys_search',
    'ddys_calendar', 'ddys_movie', 'ddys_collections', 'ddys_request_form',
  ]) {
    assert.match(helpers, new RegExp(`function\\s+${fn}\\s*\\(`));
  }
});

test('README wording is detailed, linked, and avoids misleading API wording', () => {
  const zh = read('README.zh-CN.md');
  const en = read('README.md');

  assert.match(zh, /低端影视 API/);
  assert.match(zh, /README\.md/);
  assert.match(en, /README\.zh-CN\.md/);
  const forbidden = new RegExp([
    'DDYS ' + 'Open API',
    'Open' + 'AI',
    'GP' + 'T',
    'third-party ' + 'CDN',
    '第三方 ' + 'CDN',
    '不依赖 ' + 'Composer',
    '不依赖 ' + 'npm',
  ].join('|'));
  for (const text of [zh, en]) {
    assert.doesNotMatch(text, forbidden);
  }
});
