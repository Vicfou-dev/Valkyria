<?php
namespace Valkyria\Helper;

class ListArray{

    protected $value = [];

    public function __construct(Array $array){ $this->init($array); return $this->value; }

    protected function init(Array $array){ $this->value = $array;}

    public function reset(){ $this->value = [];}

    public function get($key){ return $this->value[$key];}

    public function set($key,$value){ $this->value[$key] = $value;} 

    public function delete($key){ unset($this->value[$key]); }

    public function all(){ return $this->value; }

    public function keys() { return array_keys($this->value); }
}