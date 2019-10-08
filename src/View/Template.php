<?php
namespace Valkyria\View;

class Template{
    public $folder;

    protected $fileExentension = 'php';

    public function __construct($folder = null){
        if( $folder ){
            $this->setFolder($folder);
        }
        return $this;
    }

    protected function setFolder(String $folder){
        $this->folder = rtrim($folder,'/');
    }

    public function render(String $path,Array $args = array()){
        $template = $this->findTemplate($path);
        $output = "";
        if( $template ){
            $output = $this->renderTemplate($template,$args);
        }
        return $output;
    }

    protected function findTemplate(String $path){
        $file = "{$this->folder}/$path.{$this->fileExentension}";
        if(file_exists($file)){
            return $file;
        }
    }

    protected function renderTemplate(/*$template,$args*/){
        ob_start();
        foreach(func_get_args()[1] as $key=>$value){
            ${key} = $value;
        }
        
        require_once func_get_args()[0];
        $out = ob_get_clean();
        return $out;
    }
}