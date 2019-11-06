<?php

namespace Valkyria\Api;

class Sms extends BuilderApi{

    public function __construct($params = []){
        parent::__construct($params);
    }

    public function send(string $phoneNumber, string $sender, string $message = '') {
        
        $params = [
            'accessToken' => $this->config['token'],
            'message' => $message,
            'sender' => $sender,
            'numero' => $phoneNumber
        ];

        $curl = $this->curl;

        $curl->setParams($params);

        $reponse = $curl->get()->response([
            "inCharset" => "ISO-8859-1",
            "outCharset" => "UTF-8"
        ]);

        if( isset($reponse['error']) ) return $reponse;

        return $this->formatResponse($reponse,$message);  
            
    }

    protected function formatResponse($reponse,$message){

        $key = ['code','description','id','message'];

        $value = explode('|', $reponse);

        $value[] = $message;

        return array_combine($key,$value); 

    }
}