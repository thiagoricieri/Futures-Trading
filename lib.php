<?php

function calcula($arquivo, $acumulo, $limGanho, $limPerda){
	$content = file($arquivo);
	clean_log($arquivo);

	$soma = 0;
	$negocios = 0;

	$comprados = [];
	$vendidos = [];

	incluir($comprados, $content[0]);
	incluir($vendidos, $content[0]);
	array_shift($content);
	$quote = 0;

	foreach ($content as $k => $q) {
		$quote = (float) $q;

		list($s1, $n1) = desfaz_posicao($arquivo, $comprados, $quote, C, $limGanho, $limPerda);
		list($s2, $n2) = desfaz_posicao($arquivo, $vendidos, $quote, V, $limGanho, $limPerda);

		$soma += $s1 + $s2;
		$negocios += $n1 + $n2;

		if(deve_incluir($comprados, $acumulo, $quote)) {
			incluir($comprados, $quote);
		}
		if(deve_incluir($vendidos, $acumulo, $quote)) {
			incluir($vendidos, $quote);
		}
	}

	// Vendendo o que sobrou
	foreach ($comprados as $v){
		$total = $quote - $v;
		$soma += $total;
		$negocios++;
	}
	foreach ($vendidos as $v){
		$total = $v - $quote;
		$soma += $total;
		$negocios++;
	}

	return [$soma, $negocios];
}

function deve_incluir($src, $ac, $quote){
	if(count($src) >= $ac) return false;
	// if(in_array($quote, $src)) return false;
	return true;
}

function clean_log($arquivo){
	// $arquivo = str_replace("database/", "", $arquivo);
	// $arquivo = str_replace(".txt", "", $arquivo);
	// $prev = "output/$arquivo.csv";
	// $fp = fopen($prev, "w+") or die("Unable to open file!");
	// fwrite($fp, "\"Oper\",\"Compra\",\"Venda\",\"Resultado\",\"Pos\"\n");
	// fclose($fp);
}

function log_negocio($arquivo, $oper, $compra, $venda, $total, $pos){
	// $arquivo = str_replace("database/", "", $arquivo);
	// $arquivo = str_replace(".txt", "", $arquivo);
	// $prev = "output/$arquivo.csv";
	// $content = "";
	// if(file_exists($prev)){
	// 	$content = file_get_contents($prev);
	// }

	// $compra = number_format($compra, 1, ",", "");
	// $venda = number_format($venda, 1, ",", "");
	// $total = number_format($total, 1, ",", "");

	// $fp = fopen($prev, "w+") or die("Unable to open file!");
	// $content .= "\"$oper\",\"$compra\",\"$venda\",\"$total\",\"$pos\"\n";
	// fwrite($fp, $content);
	// fclose($fp);
}

function desfaz_posicao(
		$arquivo,
		&$source, 
		$currentQuote, 
		$tipo, 
		$limGanho, 
		$limPerda
	){
	$resultado = 0;
	$negocios = 0;
	foreach ($source as $k => $v){
		if($tipo == C) {
			$total = $currentQuote - $v;
			$currentQuote = number_format($currentQuote, 1);
			$v = number_format($v, 1);
			if($total >= $limGanho){
				$total = number_format($total, 1);
				$resultado += $total;
				unset($source[$k]);
				$negocios++;
				log_negocio($arquivo, C, $v, $currentQuote, $total, $k);
			}
			if($total <= -$limPerda){
				//$total = -$limPerda;
				$total = number_format($total, 1);
				$resultado += $total;
				unset($source[$k]);
				$negocios++;
				log_negocio($arquivo, C, $v, $currentQuote, $total, $k);
			}
		}
		if ($tipo == V){
			$total = $v - $currentQuote;
			$currentQuote = number_format($currentQuote, 1);
			$v = number_format($v, 1);
			if($total >= $limGanho){
				$total = number_format($total, 1);
				$resultado += $total;
				unset($source[$k]);
				$negocios++;
				log_negocio($arquivo, V, $currentQuote, $v, $total, $k);
			}
			if($total <= -$limPerda){
				//$total = -$limPerda;
				$total = number_format($total, 1);
				$resultado += $total;
				unset($source[$k]);
				$negocios++;
				log_negocio($arquivo, V, $currentQuote, $v, $total, $k);
			}
		}
	}
	return [$resultado, $negocios];
}

function incluir(&$src, $quote){
	$src[] = (float) $quote;
}