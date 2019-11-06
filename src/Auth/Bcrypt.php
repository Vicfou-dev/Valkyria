<?php

namespace Valkyria\Auth;

class Bcrypt{

    public function __construct(Array $option = array()){
        $this->option = $option;
    }

    public function hash(String $password){
        return password_hash($password, PASSWORD_BCRYPT,$this->option);
    }

    public function verify(String $password,String $hash){
        return password_verify($password, $hash);
    }
}