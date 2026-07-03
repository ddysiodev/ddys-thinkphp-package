<?php

namespace Ddys\ThinkPHP\Command;

use Ddys\ThinkPHP\Client;
use Ddys\ThinkPHP\Exception\DdysException;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class TestCommand extends Command
{
    protected function configure()
    {
        $this->setName('ddys:test')->setDescription('测试低端影视 API 连接');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $client = function_exists('app') ? app('ddys') : new Client();
            $types = $client->types();
            $output->writeln('<info>低端影视 API 连接成功。</info>');
            $output->writeln('类型数量：' . count((array) $types));
            return 0;
        } catch (DdysException $e) {
            $output->writeln('<error>连接失败：' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
