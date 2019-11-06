<?php

namespace Valkyria\Helper;

class Image {

    protected $directory;

    protected $filename;

    protected $path;

    protected $type;

    public function __construct(String $directory,String $filename){

        $this->directory = $directory;
        $this->filename = $filename;

        $this->path = $directory . '/' . $filename;

    }


    public function load() {

        $this->imageInfo = $this->getInfo($this->path);

        if($this->imageInfo){

            $this->type = $this->imageInfo[2];

            $this->data = $this->create();

        }

    }

    public function isLoad(){ return is_array($this->imageInfo); }

    public function resizeToHeight(Float $height) {

        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width,$height);

    }
  
    public function resizeToWidth(Float $width) {

        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width,$height);

    }
  
    public function scale(Float $scale) {

        $width = $this->getWidth() * $scale;
        $height = $this->getheight() * $scale;
        $this->resize($width,$height);

    }

    public function createFromBase64(String $data){

        $this->data = imagecreatefromstring($data); //Base64 image to Jpeg
        imageinterlace($this->data,true); // JPEG progressive

    }

    public function render(){

        switch($this->type){
            case IMAGETYPE_JPEG :
                header('Content-Type: image/jpeg');
                imagejpeg($this->data);
                break;
            case IMAGETYPE_GIF :
                header('Content-Type: image/gif');
                imagegif($this->data);
                break;
            case IMAGETYPE_PNG :
                header('Content-Type: image/png');
                imagepng($this->data);
                break;
        }
    }

    protected function create(){
        
        switch($this->type){
            case IMAGETYPE_JPEG :
                return imagecreatefromjpeg($this->path);
                break;
            case IMAGETYPE_GIF :
                return imagecreatefromgif($this->path);
                break;
            case IMAGETYPE_PNG :
                return imagecreatefrompng($this->path);
                break;
        }

    }

    public function save($image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null) {

        $this->imageType = $image_type;
        
        if( $permissions != null){
            chmod($this->path,$permissions);
        }

        switch($this->imageType){
            case IMAGETYPE_JPEG :
                imagejpeg($this->image,$this->path,$compression);
                break;
            case IMAGETYPE_GIF :
                imagegif($this->image,$this->path,$compression);
                break;
            case IMAGETYPE_PNG :
                imagepng($this->image,$this->path,$compression);
                break;
        }

    }
  
    public function resize(Float $width,Float $height) {

        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->data, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->data = $new_image;

    }  

    protected function getInfo(String $img) {
        if (file_exists($img)){
            return getimagesize($img);
        }
    }

    protected function getWidth() : Float { return imagesx($this->data); }
    protected function getHeight() : Float { return imagesy($this->data); }
}