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

    public function __construct(array $query = [], array $request = [], array $session = [],array $header = [],$content = null){
        $this->initialize($query,$request,$session,$header,$content);
    }

    protected function initialize($query,$request,$session,$header,$content){

        $this->query = new ListArray($query);
        $this->request = new ListArray($request);
        $this->session = new ListArray($session);
        $this->headers = new ListArray($header);
        $this->content = $content;

        $this->requestUri = $this->headers->get('REQUEST_URI');

    }

    public function setUri($uri){
        $this->uri = $uri;
        parse_str(parse_url($uri, PHP_URL_QUERY),$this->query);
        return $this;
    }

    public function ajax(){
        return 'XMLHttpRequest' == $this->headers->get('X-Requested-With');
    }

    public function userAgent(){
        return $this->headers->get('User-Agent');
    }

    protected function isJson(){
        return in_array($this->header('CONTENT_TYPE'),['application/json','application/javascript']); 
    }


    public function json($key = null){
        if (! isset($this->json)) {
            $this->json = new ArrayList((array) json_decode($this->getContent(), true));
        }

        if (is_null($key)) { return $this->json; }

        return $this->json->get($key);
    }


    public function getInputSource(){

        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->headers('REQUEST_METHOD'), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    public function getAuth(){
        $headerKeys = $this->header->keys();

        $Authorization = [];

        if( in_array($headerKeys,'PHP_AUTH_USER') ){

            $Authorization['PHP_AUTH_USER'] = $this->header->get('PHP_AUTH_USER');
            $Authorization['PHP_AUTH_PW'] = in_array($headerKeys,'PHP_AUTH_PW') ? $this->header->get('PHP_AUTH_PW') : '';

        } else {

            $authorizationHeader = null;

            if( in_array($headerKeys,'HTTP_AUTHORIZATION') ) {

                $authorizationHeader = $this->header->get('HTTP_AUTHORIZATION');

            } elseif( in_array($headerKeys,'REDIRECT_HTTP_AUTHORIZATION') ) {

                $authorizationHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];

            }
            if ( null !== $authorizationHeader ) {

                if ( 0 === stripos($authorizationHeader, 'basic ') ) {
                    
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);

                    if (2 == count($exploded)) {
                        list($Authorization['PHP_AUTH_USER'], $Authorization['PHP_AUTH_PW']) = $exploded;
                    }
                    
                } elseif (empty($this->header->get('PHP_AUTH_DIGEST')) && (0 === stripos($authorizationHeader, 'digest '))) {
                    
                    $Authorization['PHP_AUTH_DIGEST'] = $authorizationHeader;

                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                   
                    $Authorization['AUTHORIZATION'] = $authorizationHeader;

                }
            }
        }

        if (isset($Authorization['AUTHORIZATION'])) { 
            return [
                "type" => "Bearer", 
                "autorization" => $Authorization['AUTHORIZATION']
            ];
        }

        if (isset($Authorization['PHP_AUTH_USER'] )) { 
            return [
                "type" => "Basic",
                "authorization" => base64_encode($Authorization['PHP_AUTH_USER'].':'.$Authorization['PHP_AUTH_PW'])
            ];
        }

        elseif (isset($Authorization['PHP_AUTH_DIGEST'])) { 
            return [
                "type" => "Digest",
                "authorization" => $Authorization['PHP_AUTH_DIGEST']
            ];
        }

        return false; 
        
    }

    
}