<?php

namespace Ddys\ThinkPHP\Command;

use Ddys\ThinkPHP\Support\Url;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class RoutesCommand extends Command
{
    protected function configure()
    {
        $this->setName('ddys:routes')->setDescription('显示低端影视 ThinkPHP 扩展包路由');
    }

    protected function execute(Input $input, Output $output)
    {
        $routes = [
            Url::route(''),
            Url::route('movies'),
            Url::route('hot'),
            Url::route('search'),
            Url::route('calendar'),
            Url::route('movie/this-tempting-madness'),
            Url::route('movie/this-tempting-madness/sources'),
            Url::route('movie/this-tempting-madness/related'),
            Url::route('movie/this-tempting-madness/comments'),
            Url::route('collections'),
            Url::route('collection/example'),
            Url::route('shares'),
            Url::route('share/1'),
            Url::route('requests'),
            Url::route('activities'),
            Url::route('user/demo'),
            Url::route('types'),
            Url::route('genres'),
            Url::route('regions'),
            Url::route('api') . '?route=latest&limit=12',
            Url::route('request-submit'),
            Url::route('check'),
        ];

        foreach ($routes as $route) {
            $output->writeln($route);
        }
        return 0;
    }
}
