<?php

namespace Valkyria\Auth;

class Otp {

    protected $secret;

    protected $digest;

    protected $digits;

    public function __construct(String $secret,Array $opt = Array() ) {

      $this->digits = isset($opt['digits']) ? $opt['digits'] : 6;

      $this->digest = isset($opt['digest']) ? $opt['digest'] : 'sha1';

      $this->secret = $secret;

    }

    public function generateToken($input = null) {

        if($input == null){ $input = time(); }

        $hash = hash_hmac($this->digest, pack('N', $input), $this->secret);

        $hmac = array_values(unpack('C*', $hash));

        $offset = $hmac[19] & 0xf;

        $code = ($hmac[$offset + 0] & 0x7F) << 24 | ($hmac[$offset + 1] & 0xFF) << 16 | ($hmac[$offset + 2] & 0xFF) << 8 | ($hmac[$offset + 3] & 0xFF);

        return $code % pow(10, $this->digits);

    }


} 
