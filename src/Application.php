<?php
namespace Valkyria;

use \Valkyria\Routing\Router;
use \Valkyria\Database\Mysql;
use \Valkyria\View\Template;
use \Valkyria\Helper\ListArray;


Class Application{
    protected $basePath;
 
    protected static $config;

    protected $storagePath;

    protected $namespace;

    protected static $router;

    protected static $db;

    protected static $view;

    protected static $version;

    protected static $info = [
        "product" => "Valkyria",
        "versionMajor" => 1,
        "versionMinor" => 0,
        "versionMaj" => 0,
        "date" => "2019-06-10",
        "author" => "Victor Morel",
        "email" => "victormorel.pro@gmail.com",
        "license" => "GPL"
    ];

    public function __construct(Array $config = array()){

        self::$config = new ListArray($config);
        
        $this->createRouter();
        $this->setVersion();
        $this->createTemplate();
    }

    protected function createRouter(){
        $namespace = self::$config->get('namespace');
        
        self::$router = new Router(array_map(function($value) use ($namespace){
            return $namespace . "\\" . $value;
        },self::$config->get('router')));
    }

    protected function createDb(){
        self::$db = new Mysql(self::$config->get('db'));
    }

    protected function createTemplate(){
        self::$view = new Template(self::$config->get('view'));
    }

    protected function setVersion(){

        self::$version = sprintf("%s Version %s.%s%s, CopyRight, %s By %s (%s), License (%s)",...array_values(self::$info));

    }

    public static function getInstance($instance){
        
        return static::$$instance;
    }


}