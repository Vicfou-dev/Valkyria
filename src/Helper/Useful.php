<?php

use \Valkyria\Application;

if (! function_exists('app')) {

    function app($instance = null){

        if (is_null($instance)) {
            return Application::getInstance();
        }

        return Application::getInstance()->getOneInstance($instance);
    }
    

}

if (! function_exists('config')) {

    function config($key = null, $default = null){
        if (is_null($key)) { return app('config'); }

        else if (is_null($default)) { 
            return app('config')->get($key);
        }

        else return app('config')->set($key,$default);
    }
}

if (! function_exists('api')) {

    function api($key = null){

        $api = config('api');

        if (is_null($key) || !isset($api[$key]) ) { return $api; }

        else return $api[$key];
    }
}

if (! function_exists('build_url')) {

    function build_url($path = null,$secure = false){
        return ($secure ? 'https' : 'http') . "://" . config('domain') . '/' . $path;
    }
}

if (! function_exists('redirect')) {

    function redirect($path = null,$secure = false){
        $uri = build_url($path,$secure);
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

if (! function_exists('filter_array')) {

    function filter_array($haystack,$needle){
        return array_filter(
            $haystack,
            function ($key) use ($needle) {
                return in_array($key, $needle);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

}

if (! function_exists('generateRandomString')){

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
