<?php

namespace Valkyria\Api;

use Valkyria\Helper\Curl;

class Weather extends BuilderApi{

    protected $token;

    public function __construct(String $token,$params = array()){

        parent::__construct($config);
     
    }

    public function send(Int $longitude, Int $latitude) {
        
        $params = [
            'lon' => $longitude,
            'lat' => $latitude
        ];


        $curl = new Curl($this->config);

        $curl->setParams($params);

        $reponse = $curl->get->response();

        if( !$reponse ) return ['error' => 'cannot get answer'];

        return $reponse;  
            
    }

}