<?php
namespace Valkyria\Http;

class Response{
    private $header = array();
    private $content;
    const DEFAULT_PROTOCOL = "HTTP/1.0";
    const DEFAULT_CODE = 200;
    const DEFAULT_ERROR_CODE = 500;
    const DEFAULT_CONTENT_TYPE	= "text/html";

    public static $HTTP_CODES = array(
        200	=>	"OK"			,
        301	=>	"Moved"			,
        302	=>	"Found"			,
        400	=>	"Bad request"	,
        401	=>	"Unauthorized"	,
        403	=>	"Forbidden"		,
        404	=>	"Not Found"		,
        500	=>	"Internal Error"	,
        501	=>	"Not implemented"	,
        503	=>	"Gateway timeout"	,
    );

    public function __construct($content = "", $content_type = "", $code = 0){

        if ($code <= 0) {$code = self::DEFAULT_CODE; }
        $this->setResponse($code);
        $this->setHeader("Date", gmdate(DATE_RFC850));
        $this->setContent($content, $content_type);
        return $this;
            
    }

    public function setResponse($code = 200, $text = false){
        if ($code <= 0){
            $code = 200;
        }

        if ($text === false){
          if (!isset(self::$HTTP_CODES[$code])){
            $code = 200;
          }
          $text = self::$HTTP_CODES[$code];
        }
        $this->response = $this->getProtocol() . " $code $text";
        return $this;
    }

    protected function getProtocol(){
        if (isset($_SERVER["SERVER_PROTOCOL"])){
                return $_SERVER["SERVER_PROTOCOL"];
            }
        return self::DEFAULT_PROTOCOL;
    }

    protected function checkHeader($string) {
        $string = strtolower($string);
        if (preg_match("/^[a-z0-9\-]+$/", $string)){ return implode("-", array_map('ucfirst', explode("-", $string))); }
        return false;
    }

    public function setContent($content, $content_type = self::DEFAULT_CONTENT_TYPE){
        if(!$content_type) $content_type = self::DEFAULT_CONTENT_TYPE;
        $this->content = $content;
        $this->setHeader("Content-Type", $content_type);
        $this->setHeader("Content-Length", is_string($this->content) ? strlen($this->content) : count($this->content));
        return $this;
    }

    private function debugHeaders(){
        printf($this->response . "\n");
        foreach($this->headers as $header => $value){
                printf("%s: %s\n", $header, $value);
            }
        printf("\n");
    }
  
    public function setHeader($header, $value){
        $header = $this->checkHeader($header);
        if ($header === false) {return;}
        $this->headers[$header] = $value;
        
    }
        
    private function sendHeaders(){
        header($this->response);
        foreach($this->headers as $header => $value){
                header("$header: $value");
                
        }
    }

    public function sendJson($debug = false){
        if(is_array($this->content)) {
            $this->setContent(json_encode($this->content), 'application/json');
        }
        $this->send($debug);
    }
  
    public function send($debug = false){
        if ($debug){
            $this->debugHeaders();
        }else{
            $this->sendHeaders();
        }
        $this->sendContent();
    }
        
    private function sendContent(){ exit($this->content); }
}