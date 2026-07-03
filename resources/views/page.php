<?php
use Ddys\ThinkPHP\Support\Security;
use Ddys\ThinkPHP\Support\Url;
?><!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo Security::h($title); ?></title>
  <?php echo $assets; ?>
</head>
<body class="ddys-thinkphp-page">
  <main class="ddys-thinkphp-shell">
    <header class="ddys-thinkphp-page-header">
      <a class="ddys-thinkphp-brand" href="<?php echo Security::attr(Url::page('latest')); ?>">
        <img src="<?php echo Security::attr(Url::asset('images', 'logo.png')); ?>" alt="" width="32" height="32" />
        <span>低端影视</span>
      </a>
      <?php echo $nav; ?>
    </header>
    <section class="ddys-thinkphp-page-content">
      <?php echo $content; ?>
    </section>
  </main>
</body>
</html>
