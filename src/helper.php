<?php

use Ddys\ThinkPHP\Client;
use Ddys\ThinkPHP\Renderer;

if (!function_exists('ddys_client')) {
    function ddys_client()
    {
        if (function_exists('app')) {
            return app('ddys');
        }
        return new Client();
    }
}

if (!function_exists('ddys_render')) {
    function ddys_render($tag, array $params = [])
    {
        return (new Renderer(ddys_client()))->render($tag, $params);
    }
}

if (!function_exists('ddys_latest')) {
    function ddys_latest(array $params = [])
    {
        return ddys_client()->latest($params);
    }
}

if (!function_exists('ddys_hot')) {
    function ddys_hot(array $params = [])
    {
        return ddys_client()->hot($params);
    }
}

if (!function_exists('ddys_search')) {
    function ddys_search(array $params)
    {
        return ddys_client()->search($params);
    }
}

if (!function_exists('ddys_calendar')) {
    function ddys_calendar(array $params = [])
    {
        return ddys_client()->calendar($params);
    }
}

if (!function_exists('ddys_movie')) {
    function ddys_movie($slug)
    {
        return ddys_client()->movie($slug);
    }
}

if (!function_exists('ddys_collections')) {
    function ddys_collections(array $params = [])
    {
        return ddys_client()->collections($params);
    }
}

if (!function_exists('ddys_request_form')) {
    function ddys_request_form(array $params = [])
    {
        return (new Renderer(ddys_client()))->requestForm($params);
    }
}
