<?php
namespace Valkyria;

use \Valkyria\Helper\ListArray;

Class Application{
    protected $basePath;
 
    protected $config;

    protected $storagePath;

    protected $namespace;

    protected $instances;

    protected static $instance;

    public $router;

    public function __construct(Array $config = array()){
        
        $this->registerAliases();

        $this->instances = new ListArray();
        $this->instance('config',$config);

        $this->config = $this->getOneInstance('config');

        $this->init();
        $this->logger();
        $this->bootRouter();
    }

    public function init(){
        $this->instance('app', $this);

        static::$instance = $this;
        
        if($this->config->exist('db')){
            $this->instance('db',config('db'));
        }

        if($this->config->exist('view')){
            $this->instance('view',config('view'));
        }

    }

    protected function bootRouter(){
        $router = $this->aliases->get('router');
        $this->router = new $router;
    }

    protected function logger(){

        if($this->config->exist('logs')){
            
            $logs = (object) config('logs');

            ini_set('display_errors', $logs->display);
            error_reporting(E_ALL);

            if($logs->active == true){

                ini_set('log_errors', 1);
                ini_set('error_log', $logs->path) ;
                
            }

        }

    }

    public static function isRunningInConsole(){
         return in_array(php_sapi_name(),['phpdbg','cli']); 
    }

    public static function getConsoleArguments(){
        global $argv;return $argv;
    }

    protected function registerAliases(){

        $this->aliases = new ListArray([
            'router' => '\Valkyria\Routing\Router',
            'db' => '\Valkyria\Database\Mysql',
            'view' => '\Valkyria\View\Template',
            'request' => '\Valkyria\Http\Request',
            'response' => '\Valkyria\Http\Response',
            'config' => '\Valkyria\Helper\ListArray'
        ]);

    }

    public function getVersion(){

        $info = [
            "product" => "Valkyria",
            "versionMajor" => 1,
            "versionMinor" => 0,
            "versionMaj" => 0,
            "date" => "2019-06-10",
            "author" => "Victor Morel",
            "email" => "victormorel.pro@gmail.com",
            "license" => "GPL"
        ];

       return sprintf("%s Version %s.%s%s, CopyRight, %s By %s (%s), License (%s)",...array_values($info));

    }
    
    public function instance($abstract, $param = null){

        if( $this->aliases->exist($abstract) ){

            $instance = $this->aliases->get($abstract);

            if(!is_null($param)){

                return $this->instances->set($abstract,new $instance($param));
            }

            return $this->instances->set($abstract,new $instance);

        } 

        return $this->instances->set($abstract,$param);

        
    }
    
    public function getOneInstance($abstract){

        return $this->instances->get($abstract);

    }

    public static function getInstance() {
 
        if(is_null(self::$instance)) {
            self::$instance = new static;  
        }
    
        return self::$instance;
    }


}