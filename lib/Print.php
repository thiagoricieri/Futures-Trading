<?php

class Printable {

    // Config
    var $colnum = 20;
    var $divisor = "-";

    // Utils
    function strDivisor($d = null){
        return str_repeat($d == null ? $this->divisor : $d, $this->colnum);
    }
    function centralizaStr($str){
        $len = strlen($str);
        if($len > $this->colnum) return $str;
        $space = " ";
        $half = ceil($this->colnum/2 - $len/2);
        return str_repeat($space, $half) . $str;
    }
}
