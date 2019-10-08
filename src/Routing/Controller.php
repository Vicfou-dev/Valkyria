<?php
namespace Valkyria\Routing;

class Controller
{
 
    protected $middleware = [];

    public function middleware($middleware, array $options = []){
        $this->middleware[$middleware] = $options;
    }

}