<?php
namespace Valkyria\Helper;
use Closure;

class Pipeline{
    
    protected $passable;
    
    protected $pipes = [];
    
    protected $parameters = [];
    
    protected $method = 'handle';
    
    public function send(...$passable){
        $this->passable = $passable;
        return $this;
    }
    
    public function through($pipes){
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }
    
    public function via($method){
        $this->method = $method;
        return $this;
    }
    
    public function with(...$parameters){
        $this->parameters = $parameters;
        return $this;
    }
    
    public function then(Closure $destination){
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->curry(), $this->prepareDestination($destination)
        );
        return call_user_func_array($pipeline, $this->passable);
    }
    
    protected function prepareDestination(Closure $destination){
        return function () use ($destination) {
            return call_user_func_array($destination, func_get_args());
        };
    }
    
    protected function curry(){
        return function ($stack, $pipe) {
            return function () use ($stack, $pipe) {

                $passable = func_get_args();
                $passable[] = $stack;
                $passable = array_merge($passable, $this->parameters);

                if (is_callable($pipe)) {     
                    
                    return call_user_func_array($pipe, $passable);

                } elseif (! is_object($pipe)) {
                    list($name, $parameters) = $this->parsePipeString($pipe);
                    $pipe = new $name();
                    $parameters = array_merge($passable, $parameters);

                } else {
                    
                    $parameters = $passable;
                }
                return method_exists($pipe, $this->method)
                    ? call_user_func_array([$pipe, $this->method], $parameters)
                    : $pipe(...$parameters);
            };
        };
    }
   
    protected function parsePipeString($pipe){
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);
        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }
        return [$name, $parameters];
    }
}