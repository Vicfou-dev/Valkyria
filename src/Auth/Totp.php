<?php

namespace Valkyria\Auth;

class Totp extends Otp {

    private $interval;

    public function __construct(String $secret,Array $opt = Array()) {

        $this->interval = isset($opt['interval']) ? $opt['interval'] : 30;

        parent::__construct($secret, $opt);

    }

    public function generateToken(Int $timestamp = null): String {

        if ($timestamp === null) { $timestamp = time(); }

        return parent::generateToken( $timestamp );

    }

    public function isValidToken(String $token, Int $timestamp = null): Bool {

        if ($timestamp === null) { $timestamp = time(); }

        for($i = $timestamp - $this->interval; $i < $timestamp + $this->interval; $i++){
            if ($this->generateToken( $i ) == $token) {
                return true;
            }
        }

        return false;
    }
}  