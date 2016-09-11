<?php

function read_data($file){
	$prices = file($file);
	$output = [];

	foreach($prices as $p){
		$output[] = (float) $p;
	}

	return $output;
}

class Operacao {

	var $compra;
	var $venda;
	var $tendencia;
	var $multiplo;

	function __construct($compra, $venda, $multiplo=1, $tendencia=0) {
		$this->compra = $compra;
		$this->venda = $venda;
		$this->tendencia = $tendencia;
		$this->multiplo = $multiplo;
	}

	function diferenca() {
		return ($this->venda - $this->compra) * $this->multiplo;
	}
}

function calcular($arr, $zerar=200){

	$soma = 0;

	foreach ($arr as $i){
		$compra = $i->compra;
		$venda = $i->venda;

		if($compra < 1) $compra = $zerar;
		if($venda < 1) $venda = $zerar;

		$soma += ($venda - $compra) * $i->multiplo;
	}

	return $soma;
}

function write_oper_file($file, $arr, $zerar=200, $gerar=false){

	$output = ["\"Compra\",\"Venda\",\"Mult\",\"Dif\",\"Tend\""];
	$soma = 0;

	foreach ($arr as $i){
		if($i->compra < 1) $i->compra = $zerar;
		if($i->venda < 1) $i->venda = $zerar;

		$compra = number_format($i->compra, 1, ",", "");
		$venda = number_format($i->venda, 1, ",", "");
		$diff = number_format($i->diferenca(), 1, ",", "");

		$output[] = "\"$compra\",\"$venda\",\"$i->multiplo\",\"$diff\",\"$i->tendencia\"";
		$soma += $i->diferenca();
	}

	$somaStr = number_format($soma, 1, ",", "");
	$output[] = "\"\",\"\",\"$soma\",\"\",\"\"";

	if($gerar){
		$fp = fopen($file, "w+");
		fwrite($fp, implode("\n", $output));
		fclose($fp);
	}

	return $soma;
}

function oper_contem($src, $preco, $diff = 0){
	foreach($src as $p){
		if($p->compra == 0 && abs($p->venda - $preco) <= $diff) {
			return true;
		}
		else if($p->venda == 0 && abs($p->compra - $preco) <= $diff) {
			return true;
		}
	}
	return false;
}