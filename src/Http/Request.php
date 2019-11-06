<?php
namespace Valkyria\Http;

use Valkyria\Helper\ListArray;

class Request{

    protected $json;

    public $query;

    public $request;

    protected $session;

    protected $headers;

    protected $content;

    public $requestUri;

    public $attributes;

    public function __construct(array $request = [], array $session = [],array $header = [],$content = null){
        $this->initialize($request,$session,$header,$content);
    }

    protected function initialize($request,$session,$header,$content){

        $this->query = new ListArray();
        $this->request = new ListArray($request);
        $this->session = new ListArray($session);
        $this->headers = new ListArray($header);
        $this->content = $content;
        $this->attributes = new ListArray();

        $this->parseUri($this->headers->get('REQUEST_URI'));

    }

    public function getHttpMethod(){
        return $this->headers->get('REQUEST_METHOD');
    }

    public function ajax(){
        return 'XMLHttpRequest' === $this->headers->get('X-Requested-With');
    }

    public function userAgent(){
        return $this->headers->get('User-Agent');
    }

    public function getIp(){

        if ( !empty($this->headers->get("HTTP_CLIENT_IP")) ) return $this->headers->get("HTTP_CLIENT_IP");
        elseif ( !empty($this->headers->get("HTTP_X_FORWARDED_FOR")) ) return $this->headers->get("HTTP_X_FORWARDED_FOR");
        else return $this->headers->get("REMOTE_ADDR");
        
    }

    public function isSecure(){
        if ($this->headers->exist('HTTPS') && $this->headers->exist('HTTPS') === 'on') return true;
        elseif ( ($this->headers->exist('HTTP_X_FORWARDED_PROTO') 
                && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || ($this->headers->exist('HTTP_X_FORWARDED_SSL') 
                && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ) return true;
        else return false;
    }

    public function userAgentHttpInformation(){

        $agent = $this->headers->get("HTTP_USER_AGENT");

        if(preg_match('/Linux/i',$agent)) $os = 'Linux';
        elseif(preg_match('/Mac/i',$agent)) $os = 'Mac'; 
        elseif(preg_match('/iPhone/i',$agent)) $os = 'iPhone'; 
        elseif(preg_match('/iPad/i',$agent)) $os = 'iPad'; 
        elseif(preg_match('/Droid/i',$agent)) $os = 'Droid'; 
        elseif(preg_match('/Unix/i',$agent)) $os = 'Unix'; 
        elseif(preg_match('/Windows/i',$agent)) $os = 'Windows';
        else $os = 'Unknown';

        // Browser Detection
        if(preg_match('/Firefox/i',$agent)) $br = 'Firefox'; 
        elseif(preg_match('/Mac/i',$agent)) $br = 'Mac';
        elseif(preg_match('/Chrome/i',$agent)) $br = 'Chrome'; 
        elseif(preg_match('/Opera/i',$agent)) $br = 'Opera'; 
        elseif(preg_match('/MSIE/i',$agent)) $br = 'IE'; 
        else $bs = 'Unknown';

        return [
            'os' => $os,
            'browser' => $bs
        ];
    }

    protected function isJson(){

        if($this->headers->exist('CONTENT_TYPE')){
            return in_array($this->headers->get('CONTENT_TYPE'),['application/json','application/javascript']); 
        }

    }

    public function getLanguageBrowser(){
        return substr($this->headers->get('HTTP_ACCEPT_LANGUAGE'), 0, 2);
    }

    public function parseUri(String $uri){

        $param = array();

        if( strpos($uri,'?') !== false ){


            list($path, $qs) = explode("?", $uri, 2);
            parse_str($qs,$param);
            
            $this->query = new ListArray(array_merge(
                $this->query->all(),$param
            ));

            $this->requestUri = $path;
            
        } else $this->requestUri = $uri;

        
    }

    public function getHeaders(){
        return $this->headers;
    }

    public function json($key = null){
        if (! isset($this->json)) {
            $this->json = new ListArray((array) json_decode($this->content, true));
        }

        if (is_null($key)) { return $this->json; }

        return $this->json->get($key);
    }


    public function getInputSource(){

        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->headers->get('REQUEST_METHOD'), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    public function getAuth(){
        $headerKeys = $this->headers->keys();

        $Authorization = [];

        if( in_array('PHP_AUTH_USER',$headerKeys) ){

            $Authorization['PHP_AUTH_USER'] = $this->headers->get('PHP_AUTH_USER');
            $Authorization['PHP_AUTH_PW'] = in_array('PHP_AUTH_PW',$headerKeys) ? $this->headers->get('PHP_AUTH_PW') : '';

        } else {

            $authorizationHeader = null;

            if( in_array('HTTP_AUTHORIZATION',$headerKeys) ) {

                $authorizationHeader = $this->headers->get('HTTP_AUTHORIZATION');

            } elseif( in_array('REDIRECT_HTTP_AUTHORIZATION',$headerKeys) ) {

                $authorizationHeader = $this->headers->get('REDIRECT_HTTP_AUTHORIZATION');

            } elseif( in_array('AUTHORIZATION',$headerKeys) ) {

                $authorizationHeader = $this->headers->get('AUTHORIZATION');

            }

            if ( null !== $authorizationHeader ) {

                if ( 0 === stripos($authorizationHeader, 'basic ') ) {
                    
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);

                    if (2 === count($exploded)) {
                        list($Authorization['PHP_AUTH_USER'], $Authorization['PHP_AUTH_PW']) = $exploded;
                    }
                    
                } elseif ( $this->headers->exist('PHP_AUTH_DIGEST') && (0 === stripos($authorizationHeader, 'digest '))) {
                    
                    $Authorization['PHP_AUTH_DIGEST'] = $authorizationHeader;

                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    $Authorization['AUTHORIZATION'] = substr($authorizationHeader,7);

                }
            }
        }

        if (isset($Authorization['AUTHORIZATION'])) { 
            return [
                "type" => "bearer", 
                "autorization" => $Authorization['AUTHORIZATION']
            ];
        }

        if (isset($Authorization['PHP_AUTH_USER'] )) { 
            return [
                "type" => "basic",
                "authorization" => base64_encode($Authorization['PHP_AUTH_USER'].':'.$Authorization['PHP_AUTH_PW'])
            ];
        }

        elseif (isset($Authorization['PHP_AUTH_DIGEST'])) { 
            return [
                "type" => "digest",
                "authorization" => $Authorization['PHP_AUTH_DIGEST']
            ];
        }

        return false; 
        
    }

    
}