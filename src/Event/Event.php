<?php
namespace Valkyria\Event;

class EventListener{
    protected static $events = [];

    public static function listen($name, $callback){
        self::$event[$name][] = $callback;
    }

    public static function trigger($name,$argument = null){

        foreach(self::$events[$name] as $event => $callback){

            if($argument && is_array($argument)){ call_user_func_array($callback,$argument); }

            else if($argument && !is_array($argument)){ call_user_func($callback,$argument); }

            else { call_user_func($callback); }
        }
        
    }
}