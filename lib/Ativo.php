<?php

class Ativo {

    var $nome;
    var $simbolo;
    var $lucroPorContrato;
    var $margemDayTrade;
    var $margemNormal;
    var $hipoteses = [];

    var $local;

    function __construct($n, $s, $l){
        $this->nome = $n;
        $this->lucroPorContrato = $l;
        $this->simbolo = $s;
    }

    function buscarDadosEm($l){
        $this->local = $l;
        return $this;
    }

    function historicoDia($dia){
        return lerDados("ativos/$this->local/$dia");
    }

    function hipotese($h){
        $this->hipoteses[] = $h;
        return $this;
    }
}

class Hipotese {

    var $acumulo;
    var $ganho;
    var $perda;
    var $alavancagem;
    var $prejuizo;

    function acumular($a){
        $this->acumulo = $a;
        return $this;
    }

    function tentarGanhar($a){
        $this->ganho = $a;
        return $this;
    }

    function aguentarPerder($a){
        $this->perda = $a;
        return $this;
    }

    function pararSePrejuizoFor($a){
        $this->prejuizo = $a;
        return $this;
    }
}
