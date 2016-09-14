<?php

class Operacao {

	var $compra;
	var $venda;
	var $tendencia;
	var $multiplo;

	var $indexInicio = 0;
	var $indexFim = 0;
	var $halfSale = false;

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