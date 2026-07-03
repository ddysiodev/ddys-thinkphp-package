<?php

namespace Ddys\ThinkPHP\Command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class PublishAssetsCommand extends Command
{
    protected function configure()
    {
        $this->setName('ddys:publish-assets')->setDescription('发布低端影视前台静态资源到 public/static/ddys-thinkphp');
    }

    protected function execute(Input $input, Output $output)
    {
        $source = dirname(__DIR__, 2) . '/resources/assets';
        $root = getcwd();
        if (function_exists('app') && method_exists(app(), 'getRootPath')) {
            $root = app()->getRootPath();
        }
        $target = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'ddys-thinkphp';
        $this->copyDirectory($source, $target);
        $output->writeln('<info>静态资源已发布：</info>' . $target);
        return 0;
    }

    protected function copyDirectory($source, $target)
    {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }
        foreach (scandir($source) as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $from = $source . DIRECTORY_SEPARATOR . $name;
            $to = $target . DIRECTORY_SEPARATOR . $name;
            if (is_dir($from)) {
                $this->copyDirectory($from, $to);
            } else {
                copy($from, $to);
            }
        }
    }
}
