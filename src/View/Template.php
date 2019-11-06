<?php
namespace Valkyria\View;

class Template{
    public $folder;

    protected $fileExentension = 'php';

    protected $template;

    public $templates = [];

    protected $subDirectory;

    public function __construct($config){

        if(is_string($config)){
            $this->setFolder($config);

        } else if (is_array($config)){

            if(isset($config['path'])){ $this->setFolder($config['path']); }

            if(isset($config['folder'])){ $this->subDirectory = $config['folder']; }

            if(isset($config['template'])){ $this->templates = $config['template']; }
        }
        
        return $this;
    }

    protected function setFolder(String $folder){
        $this->folder = rtrim($folder,'/');
    }

    public function setTemplate(String $key){
        if(isset($this->templates[$key])){
            $this->template = $this->templates[$key];
        }

    }

    public function render(String $path,Array $args = array()){
        $view = $this->find($path);
        $output = "";
        if( $view ){
            $output = $this->renderView($view,$args);
        }
        if( is_string($this->template) && ($template = $this->find($this->template,false)) ){
            $args['view'] = $output;
            $output = $this->renderView($template,$args);
        }
        return $output;
    }

    protected function find(String $path,Bool $view = true){
        if(!is_null($this->subDirectory) && $view){
            $path = $this->subDirectory . '/' . $path;
        }
        $file = "{$this->folder}/$path.{$this->fileExentension}";
        if(file_exists($file)){
            return $file;
        }
    }

    protected function renderView(/*$template,$args*/){
        ob_start();
        foreach(func_get_args()[1] as $key=>$value){
            ${$key} = $value;
        }
        
        require_once func_get_args()[0];
        $out = ob_get_clean();
        return $out;
    }
}