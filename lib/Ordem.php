<?php

require_once("Printable.php");

class Ordem extends Printable {

    var $conta = "";
    var $ativo = "";
    var $vendas = 0;
    var $compras = 0;
    var $precoAtual = 0;
    var $estrategia = "";

    function __construct($ativo, $conta, $precoAtual){
        $this->conta = $conta;
        $this->ativo = $ativo;
        $this->precoAtual = $precoAtual;
    }

    function operarNa($estrategia){
        $this->estrategia = $estrategia;
        return $this;
    }

    function encerrar($qtd){
        if($this->estrategia == Trade::ALTA){
            $this->vender($qtd);
        } else {
            $this->comprar($qtd);
        }
    }

    function posicionar($qtd){
        if($this->estrategia == Trade::ALTA){
            $this->comprar($qtd);
        } else {
            $this->vender($qtd);
        }
    }

    function vender($qtd){
        $this->vendas += $qtd;
    }
    function comprar($qtd){
        $this->compras += $qtd;
    }
    function zerar(){
        $this->vendas = 0;
        $this->compras = 0;
    }

    function report(){
        $r = ["\n"];
        $r[] = $this->centralizaStr("ORDENS " . strtoupper($this->conta));
        $r[] = $this->strDivisor();

        if($this->compras > 0){
            $r[] = "$this->ativo: Compre $this->compras cts a $this->precoAtual pts;";
        }
        if($this->vendas > 0){
            $r[] = "$this->ativo: Venda $this->vendas cts a $this->precoAtual pts;";
        }
        return implode("\n", $r);
    }
}
