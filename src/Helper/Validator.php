<?php

namespace Valkyria\Helper;

use \DateTime;

class Validator {

    protected $validate = [

        'boolean' => FILTER_VALIDATE_BOOLEAN,
        'int' => FILTER_VALIDATE_INT,
        'float' => FILTER_VALIDATE_FLOAT,
        'email' => FILTER_VALIDATE_EMAIL,
        'ip' => FILTER_VALIDATE_IP,
        'url' => FILTER_VALIDATE_URL,
        'regexp' => FILTER_VALIDATE_REGEXP

    ];

    protected $sanitize = [

        'email' => FILTER_SANITIZE_EMAIL,
        'encode' => FILTER_SANITIZE_ENCODED,
        'quotes' => FILTER_SANITIZE_MAGIC_QUOTES,
        'float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'int' => FILTER_SANITIZE_NUMBER_INT,
        'special' => FILTER_SANITIZE_SPECIAL_CHARS,
        'string' => FILTER_SANITIZE_STRING,
        'url' => FILTER_SANITIZE_URL

    ];

    protected $result = [

        'missing' => [],
        'notvalidate' => []
        
    ];
    protected $filters = [];

    public function __construct(Array $data,Array $filters = []) {

        $this->data = $data;
        $this->filters = $filters;

    }

    public function execute(){

        foreach ($this->filters as $key => $tab) {

            $this->filter($tab);

        }

    }

    public function filter($tab){

        if(is_string($tab)){
            return $this->exist($tab);
        }

        if(!isset($tab['value'])){ return; }

        if(!$this->exist($tab['value'])) { return; }

        $value = $tab['value'];
        

        if(isset($tab['validate'])){

            $this->validate($value,$tab['validate']);
            
        }

        if(isset($tab['sanitize'])){ $this->sanitize($value,$tab['sanitize']); }

    }

    public function getError(){ return $this->result; }

    public function isError(){

        if(count($this->result['missing'])){
            return [
                "description" => "Parameter(s) missing : " ,
                "content" => $this->result['missing']
                ];
        }

        if(count($this->result['notvalidate'])){
            return [
                "description" => "Parameter(s) not valid " ,
                "content" => $this->result['notvalidate']
                ];
        }

        return false;
    }

    protected function addError($error){ $this->result[ $error[0] ][] = $error[1];}

    protected function validateDate($date,$message, $format = 'Y-m-d') {
        
        $d = DateTime::createFromFormat($format, $date);
        if( !($d && $d->format($format) === $date) ){
            $this->addError(['notvalidate',$date .' not matching ' . $message]);
        }else return true;

    }

    protected function validateRegex($value,$message,$regex) {

        if( !(preg_match("%" . $regex . "%",$value)) ){
            $this->addError(['notvalidate',$value .' not a valid ' . $message]);
        }else return true;

    }

    protected function validateLength($value,$info) {

        $message = $info['message'];

        if(isset($info['less'])){

            $regex = "^\w{0," . $info['less'] . "}$";
            $message .= ', length need to be less than ' . $info['less'];

        }else if(isset($info['more'])){

            $regex = "^\w{" . $info['more'] . ",}$";
            $message .= ' length need to be more than ' . $info['more'];

        }else if(isset($info['beetween']) && is_array($info['beetween']) ){

            $regex = "^\w{" . $info['beetween'][0] . "," . $info['beetween'][1]. "}$";
            $message .= ' length need to be more beetwen ' . $info['beetween'][0] . ' and ' . $info['beetween'][1];

        }


        return $this->validateRegex($value,$message,$regex);

    }

    protected function validate($value,$validate){
        if(is_string($validate)) {

            if( !$this->sanitize($value,$validate) ) { return; }

        } else if(is_array($validate)){

            if( $validate['type'] == 'date' ) $this->validateDate($this->data[$value],$validate['message'],$validate['match']);
            else if($validate['type'] == 'regex') $this->validateRegex($this->data[$value],$validate['message'],$validate['match']);
            else if($validate['type'] == 'length') $this->validateLength($this->data[$value],$validate);
        }
    }

    protected function exist($value){

        if( !isset($this->data[$value]) ){
            $this->addError(['missing',$value]);
        }else return true;

    }

    protected function sanitize($value,$filter){

        if( !filter_var($this->data[$value],$this->validate[$filter]) ){
            $this->addError(['notvalidate',$this->data[$value].' not a valid '. $filter]);
        } else return true;

    }

}