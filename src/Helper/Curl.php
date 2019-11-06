<?php

namespace Valkyria\Helper;

class Curl {

    protected $cert;

    protected $header;

    protected $url;

    protected $params;

	public function __construct($conf) {

        $this->cert = isset($conf['cert'])  ? $conf['cert'] : '';

        $this->header = isset($conf['header'])  ? $conf['header'] : ["Content-Type: application/json"];

        $this->url = isset($conf['url'])  ? $conf['url'] : '';
        
    }

    public function setParams( $params ){

        $this->params = $params;

    }
    
    protected function newRequest( $method, $data = array()){

        $uri = !is_null($this->params) ? $this->url . "?" . http_build_query($this->params) : $this->url;

        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_CAINFO, $this->cert); 

        if( in_array($method, array("PUT","POST","DELTE")) ){

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(                                                                          
                $this->header, 
                array(
                    'Content-Length: ' . strlen($data_string))
                )                                                                                                                                                  
            ); 

        }

        $result = curl_exec($ch);

		if( $result === false ) {

            $this->response = [ 'error' => curl_error($ch) ];
            
        } else $this->response = $result;

        return $this;
        
    }

    public function response($type = "json") {

        if (is_array($this->response)) return $this->response;

        switch($type) {
            case 'json' : return json_encode($this->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            case 'array' : return json_decode($this->response, true);
            case is_array($type): return iconv($type["inCharset"], $type["outCharset"],$this->response);
            default: return $this->response;
            
        }
    }

	public function get(Array $data = array()){

        return $this->newRequest('GET');
        
    }
    
    public function post(Array $data){

        return $this->newRequest('POST', $data);
        
    }
    
    public function put(Array $data){

        return $this->newRequest('PUT', $data);
        
    }
    
    public function delete(Array $data){

        return $this->newRequest('POST', $data);
        
    }
    
    public function patch(Array $data){

        return $this->newRequest('patch', $data);
        
    }

}