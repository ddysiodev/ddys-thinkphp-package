import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

const requiredFiles = [
  'composer.json',
  'README.md',
  'README.zh-CN.md',
  'LICENSE',
  'config/ddys.php',
  'routes/ddys.php',
  'src/DdysService.php',
  'src/Client.php',
  'src/Renderer.php',
  'src/helper.php',
  'src/Cache/Repository.php',
  'src/Facade/Ddys.php',
  'src/Http/Controller/DdysController.php',
  'src/Support/Arr.php',
  'src/Support/Config.php',
  'src/Support/Security.php',
  'src/Support/Url.php',
  'src/Command/TestCommand.php',
  'src/Command/ClearCacheCommand.php',
  'src/Command/RoutesCommand.php',
  'src/Command/PublishAssetsCommand.php',
  'resources/assets/css/frontend.css',
  'resources/assets/js/frontend.js',
  'resources/assets/images/icon-16.png',
  'resources/assets/images/icon-32.png',
  'resources/assets/images/icon-192.png',
  'resources/assets/images/icon-512.png',
  'resources/assets/images/logo.png',
  'resources/views/page.php',
];

const clientMethods = [
  'request', 'get', 'post', 'delete', 'data', 'paginated', 'movies', 'latest',
  'hot', 'search', 'suggest', 'calendar', 'movie', 'sources', 'related',
  'comments', 'types', 'genres', 'regions', 'collections', 'collection',
  'shares', 'share', 'requests', 'createRequest', 'activities', 'user', 'me',
  'createComment', 'deleteComment', 'reportInvalidResource', 'follow',
  'unfollow', 'setFollow', 'proxy', 'normalizeRequestInput',
];

const routes = [
  "Route::get('',",
  "Route::get('/',",
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
];

const commandNames = [
  'ddys:test',
  'ddys:clear-cache',
  'ddys:routes',
  'ddys:publish-assets',
];

const forbiddenText = [
  'DDYS ' + 'Open API',
  'Open' + 'AI',
  'GP' + 'T',
  'third-party ' + 'CDN',
  '\u7b2c\u4e09\u65b9 CDN',
  '\u4e0d\u4f9d\u8d56 Composer',
  '\u4e0d\u4f9d\u8d56 npm',
];

const sensitivePatterns = [
  /ghp_[A-Za-z0-9_]{20,}/,
  /github_pat_[A-Za-z0-9_]{20,}/,
  /npm_[A-Za-z0-9_]{20,}/,
  /sk-[A-Za-z0-9_-]{20,}/,
];

function fail(message) {
  throw new Error(message);
}

function rel(file) {
  return file.replaceAll('\\', '/');
}

function read(file) {
  return fs.readFileSync(path.join(root, file), 'utf8');
}

function assertIncludes(file, needle) {
  const text = read(file);
  if (!text.includes(needle)) {
    fail(`${file} is missing ${needle}`);
  }
}

function walk(dir) {
  const out = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (['.git', 'vendor', 'runtime', 'node_modules'].includes(entry.name)) {
      continue;
    }
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      out.push(...walk(full));
    } else {
      out.push(full);
    }
  }
  return out;
}

function isText(file) {
  return /\.(php|json|md|mjs|js|css|txt|xml|yml|yaml|gitignore)$/i.test(file);
}

function checkPhpShape(file) {
  const text = read(file);
  if (text.charCodeAt(0) === 0xfeff) {
    fail(`${file} has a UTF-8 BOM`);
  }
  if (text.includes('\uFFFD')) {
    fail(`${file} contains replacement characters`);
  }
  if (file.endsWith('.php') && file !== 'resources/views/page.php' && /\?>\s*$/.test(text)) {
    fail(`${file} should not end with a closing PHP tag`);
  }

  const stack = [];
  const pairs = { ')': '(', ']': '[', '}': '{' };
  let state = 'code';
  let line = 1;

  for (let i = 0; i < text.length; i++) {
    const ch = text[i];
    const next = text[i + 1] || '';
    if (ch === '\n') {
      line++;
    }

    if (state === 'line') {
      if (ch === '\n') state = 'code';
      continue;
    }
    if (state === 'block') {
      if (ch === '*' && next === '/') {
        state = 'code';
        i++;
      }
      continue;
    }
    if (state === 'single') {
      if (ch === '\\') {
        i++;
      } else if (ch === "'") {
        state = 'code';
      }
      continue;
    }
    if (state === 'double') {
      if (ch === '\\') {
        i++;
      } else if (ch === '"') {
        state = 'code';
      }
      continue;
    }

    if (ch === '/' && next === '/') {
      state = 'line';
      i++;
      continue;
    }
    if (ch === '#') {
      state = 'line';
      continue;
    }
    if (ch === '/' && next === '*') {
      state = 'block';
      i++;
      continue;
    }
    if (ch === "'") {
      state = 'single';
      continue;
    }
    if (ch === '"') {
      state = 'double';
      continue;
    }

    if (ch === '(' || ch === '[' || ch === '{') {
      stack.push({ ch, line });
      continue;
    }
    if (pairs[ch]) {
      const last = stack.pop();
      if (!last || last.ch !== pairs[ch]) {
        fail(`${file}:${line} has an unmatched ${ch}`);
      }
    }
  }

  if (state !== 'code') {
    fail(`${file} ends inside ${state} state`);
  }
  if (stack.length) {
    const last = stack.pop();
    fail(`${file}:${last.line} has an unmatched ${last.ch}`);
  }
}

function pngSize(file) {
  const buffer = fs.readFileSync(path.join(root, file));
  if (buffer.toString('ascii', 1, 4) !== 'PNG') {
    fail(`${file} is not a PNG`);
  }
  return {
    width: buffer.readUInt32BE(16),
    height: buffer.readUInt32BE(20),
  };
}

function checkComposer() {
  const composer = JSON.parse(read('composer.json'));
  if (composer.name !== 'ddysiodev/ddys-thinkphp-package') {
    fail('composer package name is wrong');
  }
  if (composer.type !== 'library') {
    fail('composer type must be library');
  }
  if (composer.require.php !== '>=7.2.5') {
    fail('php version constraint should support ThinkPHP 6.1');
  }
  if (!composer.require['topthink/framework']?.includes('^6.1') || !composer.require['topthink/framework']?.includes('^8.0')) {
    fail('topthink/framework constraint must cover 6.1 and 8.0');
  }
  if (composer.autoload?.['psr-4']?.['Ddys\\ThinkPHP\\'] !== 'src/') {
    fail('missing PSR-4 autoload');
  }
  if (!composer.autoload?.files?.includes('src/helper.php')) {
    fail('missing helper autoload file');
  }
  if (!composer.extra?.think?.services?.includes('Ddys\\ThinkPHP\\DdysService')) {
    fail('missing ThinkPHP service discovery');
  }
  if (composer.extra?.think?.config?.ddys !== 'config/ddys.php') {
    fail('missing ThinkPHP config publishing');
  }
}

function checkIcons() {
  const expected = {
    'resources/assets/images/icon-16.png': 16,
    'resources/assets/images/icon-32.png': 32,
    'resources/assets/images/icon-192.png': 192,
    'resources/assets/images/icon-512.png': 512,
    'resources/assets/images/logo.png': 32,
  };
  for (const [file, size] of Object.entries(expected)) {
    const actual = pngSize(file);
    if (actual.width !== size || actual.height !== size) {
      fail(`${file} expected ${size}x${size}, got ${actual.width}x${actual.height}`);
    }
  }
}

function checkText() {
  for (const file of walk(root)) {
    const relative = rel(path.relative(root, file));
    if (/(^|\/)(\.env|vendor|runtime|node_modules)(\/|$)/i.test(relative) || /(^|\/)cache(\/|$)/.test(relative) || /\.(zip|log|bak|tmp)$/i.test(relative)) {
      fail(`forbidden repository file: ${relative}`);
    }
    if (!isText(relative)) {
      continue;
    }
    const text = fs.readFileSync(file, 'utf8');
    if (text.includes('\uFFFD')) {
      fail(`${relative} contains replacement characters`);
    }
    for (const forbidden of forbiddenText) {
      if (text.includes(forbidden)) {
        fail(`${relative} contains forbidden text: ${forbidden}`);
      }
    }
    for (const pattern of sensitivePatterns) {
      if (pattern.test(text)) {
        fail(`${relative} appears to contain a secret`);
      }
    }
  }
}

function checkStructure() {
  for (const file of requiredFiles) {
    if (!fs.existsSync(path.join(root, file))) {
      fail(`missing required file: ${file}`);
    }
  }

  for (const file of walk(path.join(root, 'src')).filter((item) => item.endsWith('.php'))) {
    checkPhpShape(rel(path.relative(root, file)));
  }
  checkPhpShape('config/ddys.php');
  checkPhpShape('routes/ddys.php');
  checkPhpShape('resources/views/page.php');

  const client = read('src/Client.php');
  for (const method of clientMethods) {
    if (!new RegExp(`function\\s+${method}\\s*\\(`).test(client)) {
      fail(`Client is missing ${method}()`);
    }
  }
  if (!client.includes('proxy_allow_routes') || !client.includes('normalizeRequestInput')) {
    fail('Client is missing proxy/request form safeguards');
  }

  const routeText = read('routes/ddys.php');
  for (const route of routes) {
    if (!routeText.includes(route)) {
      fail(`routes/ddys.php missing ${route}`);
    }
  }

  const renderer = read('src/Renderer.php');
  for (const token of ['shows', 'cn_name', 'episode', 'is_premiere', 'is_finale', 'requestForm', 'sourcesHtml', 'dictionaryHtml']) {
    if (!renderer.includes(token)) {
      fail(`Renderer is missing ${token}`);
    }
  }
  if (renderer.includes("return [$data];") && !renderer.includes("isset($data[$key]) && is_array($data[$key])")) {
    fail('Renderer list normalization can mis-handle empty paginated data');
  }

  const service = read('src/DdysService.php');
  for (const klass of ['TestCommand::class', 'ClearCacheCommand::class', 'RoutesCommand::class', 'PublishAssetsCommand::class']) {
    if (!service.includes(klass)) {
      fail(`service does not register ${klass}`);
    }
  }

  const commandText = commandNames.map((name) => {
    const file = {
      'ddys:test': 'src/Command/TestCommand.php',
      'ddys:clear-cache': 'src/Command/ClearCacheCommand.php',
      'ddys:routes': 'src/Command/RoutesCommand.php',
      'ddys:publish-assets': 'src/Command/PublishAssetsCommand.php',
    }[name];
    return read(file);
  }).join('\n');
  for (const name of commandNames) {
    if (!commandText.includes(name)) {
      fail(`missing command ${name}`);
    }
  }

  assertIncludes('README.zh-CN.md', 'README.md');
  assertIncludes('README.md', 'README.zh-CN.md');
  assertIncludes('README.zh-CN.md', '\u4f4e\u7aef\u5f71\u89c6 API');
  assertIncludes('README.zh-CN.md', 'ddys-thinkphp-package');
  assertIncludes('README.zh-CN.md', 'ddys:test');
  assertIncludes('README.zh-CN.md', 'Nginx');
  assertIncludes('README.zh-CN.md', 'Apache');
  assertIncludes('README.md', 'ddys-thinkphp-package');
}

checkComposer();
checkStructure();
checkIcons();
checkText();

console.log('ThinkPHP package checks passed.');
