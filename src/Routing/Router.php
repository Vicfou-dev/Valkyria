<?php
namespace Valkyria\Routing;

use Valkyria\Http\Request;
use Valkyria\Http\Response;
use Valkyria\Helper\Pipeline;
use Closure;

class Router{
    protected $groupStack = [];

    protected $routes = [];

    public $namedRoutes = [];

    protected $actionGroup = [
        'middleware' => [],
        'prefix' => [],
        'namespace' => []
    ];

    protected $stackGroup;

    protected $supportedHttpMethods = [
        "GET",
        "POST",
        "PUT",
        "DELETE",
        "HEAD",
        "OPTIONS"
    ];

    protected $app;

    protected $namespace;

    protected $pathMiddleware;

    public function __construct(){

        $this->request = new Request(
            isset($_POST) ? $_POST : [],
            isset($_SESSION) ? $_SESSION : [],
            isset($_SERVER) ?  $_SERVER : [],
            file_get_contents('php://input'));

        $this->response = new Response;
    }

    protected function newRoute($methodHttp, $route, $action){

        if(count($this->actionGroup['middleware'])){ 
            
            $action = ['middleware' => $this->actionGroup['middleware'],$action]; 
          
        }

        if(count($this->actionGroup['prefix'])){ 

            $route = preg_replace('/\/+/','/',
                '/' . implode('/',$this->actionGroup['prefix']) . '/' . $route
            );
        
        }

        $route = $this->formatRoute($route);
        
        if (is_array($methodHttp)) {
            foreach ($methodHttp as $method) {

                $this->routes[$method.$route] = ['method' => $method,'route' => $route, 'action' => $action,'namespace' => $this->actionGroup['namespace']];          
            }
        }else{
            $method = $methodHttp;

            $this->routes[$method.$route] = ['method' => $method,'route' => $route,'action' => $action,'namespace' => $this->actionGroup['namespace']];

        } 
    }

    protected function sendThroughPipeline(array $middleware,Closure $then){
        if (count($middleware) ) {
            return (new Pipeline($this))
                ->send($this->request,$this->response)
                ->through($middleware)
                ->then($then);
        }
        return $then($this->request,$this->response);
    }

    protected function findRoute($route,$uri,$default = null){

        $parameters = [];

        $route = preg_replace_callback('/\{(.*?)(:.*?)?(\{[0-9,]+\})?\}/', function ($matches) use ($route,&$parameters) {
            $parameters[] = $matches[1];
            return "(.*)";
        }, $route);

        if(preg_match_all("@^{$route}$@",$uri,$matches)){
   
            if (count($parameters)) {
                $parameters = array_combine($parameters,$matches[1]);
                $uri .= '?'.http_build_query($parameters);
            }
    
            return $uri;
        }

        return $default;
    }

    protected function foreachRoute(&$Request){
        foreach ($this->routes as $route => $info) {
            if( ($uri = $this->findRoute( $info['route'],$Request->requestUri ))) {
                $Request->parseUri($uri);
                return $info;
            }
        }
        return;
    }


    public function resolve(){
        $route = $this->foreachRoute($this->request);
        if(is_null($route)){
            return (new Response("cannot resolve route {$this->request->requestUri}","text/html",404))->send();
        }
        extract($route);

        $action = $this->analyseAction($action);

        $method = array_pop($action);

        if( ($controller = array_shift($action)) !== false){
            $namespace[] = $controller;
            $instanceName = implode('\\',$namespace);

            $instance = new $instanceName;
            foreach($this->groupStack as $middleware){
                $instance->middleware($middleware);
            }

            $function = [$instance,$method];
            
        } elseif(is_callable($method)) {
            $function = $method;
        }

        $response = $this->sendThroughPipeline($this->groupStack,function($Request,$Response) use ($function){;
            return $function($Request,$Response);
        });

        if($response instanceof Response){
            $preparedResponse = $response;
        } else {
            $preparedResponse = new Response($response);
            
        }
        
        $preparedResponse->send();

    }

    public function middleware($pathMiddleware){
        $this->pathMiddleware = $pathMiddleware;
    }

    protected function analyseAction($action){

        if(is_string($action)){
            return explode('@',$action);
            
        }else if(is_array($action)){
            return $this->analyseAction(
                $this->updateGroupStack($action)
            );

        }
         else if(is_callable($action)){
            return [false,$action];
        }
    }

    protected function formatRoute($route){
        $result = rtrim($route, '/');
        if ($result === ''){ return '/'; }
        return $result;
    }
    
    protected function updateGroupStack(array $attributes){
        
        if (isset($attributes['middleware'])){

            if(is_string($attributes['middleware'])) {
                $attributes['middleware'] = explode('|', $attributes['middleware']);
            
            }
            foreach($attributes['middleware'] as $key => $value){
                $this->actionGroup['namespace'];
                $this->groupStack[] = $this->pathMiddleware.'\\'. $value;
            }

            return isset($attributes[0]) ? $attributes[0] : null;

        }
        
    }

    protected function groupMiddleware($oldMiddleware,$newMiddleware){

        $concatMiddleware = [];

        if(is_string( $oldMiddleware )){ 
            $oldMiddleware = explode('|',$oldMiddleware) ;
        }
        
        foreach($oldMiddleware as $index => $middleware){
            $concatMiddleware[] = $middleware;
        }

        if(is_string( $newMiddleware )){ 
            $newMiddleware = explode('|',$newMiddleware) ;
        }
        
        foreach($newMiddleware as $index => $middleware){
            $concatMiddleware[] = $middleware;
        }

        return $concatMiddleware;

    }

    public function group(array $attributes, $callback){

        $this->actionGroup['middleware'] = $this->groupMiddleware(
            $this->actionGroup['middleware'],
            isset($attributes['middleware']) ? $attributes['middleware'] : []
        );

        if(isset($attributes['middleware'])){
            $this->stackGroup[] = 'middleware';
        }
        
        if(isset($attributes['prefix'])){
            $this->actionGroup['prefix'][] = $attributes['prefix'];
            $this->stackGroup[] = 'prefix';
        }

        if(isset($attributes['namespace'])){
            $this->actionGroup['namespace'][] = $attributes['namespace'];
            $this->stackGroup[] = 'namespace';
        }

        //$this->updateGroupStack($attributes);
        call_user_func($callback, $this);
        
        for($i = 0; $i < count($attributes); $i++){
            $key = array_pop($this->stackGroup);
            array_pop($this->actionGroup[$key]);
        }


    }

    public function head($route, $action){
        $this->newRoute('HEAD', $route, $action);
        return $this;
    }

    public function get($route, $action){
        $this->newRoute('GET', $route, $action);
        return $this;
    }

    public function post($route, $action){
        $this->newRoute('POST', $route, $action);
        return $this;
    }

    public function put($route, $action){
        $this->newRoute('PUT', $route, $action);
        return $this;
    }

    public function patch($route, $action){
        $this->newRoute('PATCH', $route, $action);
        return $this;
    }

    public function delete($route, $action){
        $this->newRoute('DELETE', $route, $action);
        return $this;
    }

    public function options($route, $action){
        $this->newRoute('OPTIONS', $route, $action);
        return $this;
    }

    public function getRoutes(){
        return $this->routes;
    }
}
