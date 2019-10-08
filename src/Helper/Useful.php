<?php

use \Valkyria\Application;

if (! function_exists('app')) {

    function app($instance){
        return Application::getInstance($instance);
    }

}

if (! function_exists('config')) {

    function config($key = null, $default = null){
        if (is_null($key)) { return app('config'); }

        else if (is_null($default)) { return app('config')->get($key); }

        else return app('config')->set($key,$default);
    }
}

if (! function_exists('redirect')) {

    function redirect($path = null,$secure = false){
        $uri = ($secure ? 'https' : 'http') . "://" . config('domain') . '/' . $path;
        header("Location: {$uri}");
        die();
    }
}

if (! function_exists('view')) {

    function view($view = null, $data = []){
        $template = app('view');
        
        if (func_num_args() === 0) { return $template; }

        return $template->render($view, $data);
    }
}
