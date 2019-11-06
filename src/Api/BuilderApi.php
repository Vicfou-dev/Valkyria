<?php

namespace Valkyria\Api;

use Valkyria\Helper\Curl;

class BuilderApi {

    protected $config;

    protected $curl;

    public function __construct(Array $config = array()){

        $this->config = $config;
        $this->curl = new Curl($config);
        
    }

}