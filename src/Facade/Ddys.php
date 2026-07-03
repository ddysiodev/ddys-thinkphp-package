<?php

namespace Ddys\ThinkPHP\Facade;

use Ddys\ThinkPHP\Client;
use think\Facade;

class Ddys extends Facade
{
    protected static function getFacadeClass()
    {
        return Client::class;
    }
}
