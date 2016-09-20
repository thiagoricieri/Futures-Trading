<?php

function media_movel($src, $steps){
	if(count($src) < $steps) $steps = count($src);
	$slice = array_slice($src, count($src) - $steps, $steps);
	$sum = array_sum($slice);
	return $sum / count($slice);
}

function tendenciaAbsoluta($src){
	$l = $src[0];
	$fator = 0;
	foreach($src as $i){
		$diff = $i - $l;
		$fator += $diff;
		$l = $i;
	}
	return $fator;
}

function tendencia_relativa($src, $oper){
	if($oper->indexInicio + 4 <= count($src) - $oper->indexInicio){
		$slice = array_slice($src, $oper->indexInicio);
		return tendenciaAbsoluta($slice);
	}
	return 0;
}

function removeArquivosOutput($dirOutput){
	$files = glob($dirOutput . "*");
	foreach($files as $file){
		if(is_file($file))
			unlink($file);
	}
}

function money($float){
	return number_format($float, 2, ",", ".");
}

function pts($float){
	return number_format($float, 1, ",", ".");
}

function lerDados($file){
	$prices = file($file);
	$output = [];

	foreach($prices as $p){
		$output[] = (float) $p;
	}

	return $output;
}

function calcular($arr, $zerar=200, $encerrar=false){

	$soma = 0;

	foreach ($arr as $i){
		$compra = $i->compra;
		$venda = $i->venda;

		if($compra < 1) $compra = $zerar;
		if($venda < 1) $venda = $zerar;

		if($i->compra < 1 && $encerrar) $i->compra = $zerar;
		if($i->venda < 1 && $encerrar) $i->venda = $zerar;

		$soma += ($venda - $compra) * $i->multiplo;
	}

	return $soma;
}

function gravarOperacaoNoArquivo($file, $arr, $zerar=200){

	$output = ["\"Compra\",\"Venda\",\"Mult\",\"Dif\",\"Tend\""];
	$soma = 0;

	foreach ($arr as $i){
		if($i->compra < 1) $i->compra = $zerar;
		if($i->venda < 1) $i->venda = $zerar;

		$compra = pts($i->compra);
		$venda = pts($i->venda);
		$diff = pts($i->diferenca());

		$output[] = "\"$compra\",\"$venda\",\"$i->multiplo\",\"$diff\",\"$i->tendencia\"";
		$soma += $i->diferenca();
	}

	$somaStr = pts($soma);
	$output[] = "\"\",\"\",\"$soma\",\"\",\"\"";

	$fp = fopen($file, "w+");
	fwrite($fp, implode("\n", $output));
	fclose($fp);

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
