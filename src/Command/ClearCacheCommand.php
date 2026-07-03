<?php

namespace Ddys\ThinkPHP\Command;

use Ddys\ThinkPHP\Cache\Repository;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class ClearCacheCommand extends Command
{
    protected function configure()
    {
        $this->setName('ddys:clear-cache')->setDescription('清理低端影视 API 缓存');
    }

    protected function execute(Input $input, Output $output)
    {
        $cache = function_exists('app') ? app(Repository::class) : new Repository();
        $count = $cache->clear();
        $output->writeln('<info>已清理缓存键：' . $count . '</info>');
        return 0;
    }
}
