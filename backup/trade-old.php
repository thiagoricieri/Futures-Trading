<?php

include_once("lib.php");

define("C", "C");
define("V", "V");

$arquivos = [
	"ago-22.txt", "ago-23.txt", "ago-24.txt", "ago-25.txt",
	"ago-26.txt", "ago-29.txt", "ago-30.txt", "ago-31.txt",
	"set-1.txt", "set-2.txt", "set-5.txt", "set-6.txt"
];
$matrizGanho = [2, 3, 4, 5, 6, 7, 8];
$matrizPerda = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
$matrizGanho = [2];
$matrizPerda = [5];
$ganhoScore = [];
$matrizAcumulo = [4];
$simulacaoContratos = [2, 4, 6];

$acumuloCompare = [];
$acumuloSoma = [];

foreach ($arquivos as $arq){
	$maior = 0;
	$maiorDescr = "";
	$melhorGanho = 0;
	$melhorAcumulo = 0;

	$acumuloCompare[$arq] = [];

	foreach($matrizAcumulo as $acumulo){

		foreach ($matrizGanho as $g=> $gv){
			$limGanho = $gv;

			foreach ($matrizPerda as $p => $pv) {
				$limPerda = $pv;

				list($soma, $negocios) = calcula("database/$arq", $acumulo, $limGanho, $limPerda);

				if($maior < $soma) {
					$maior = $soma;
					$ratio = number_format($soma/$negocios, 2, ",", ".");
					$maiorDescr = "($limGanho, $limPerda)	$soma pontos,	$negocios negócios	($acumulo ac.)";
					$melhorGanho = "($limGanho, $limPerda)";
					$melhorAcumulo = $acumulo;
				}
			}
		}
		$acumuloCompare[$arq][$acumulo] = $maior;
	}

	if(!isset($ganhoScore[$melhorGanho])) $ganhoScore[$melhorGanho] = 1;
	else $ganhoScore[$melhorGanho]++;

	echo "==========\n";
	echo "MELHOR $arq	$maiorDescr\n";

	foreach($simulacaoContratos as $c){
		$lucro = number_format($c * 10 * $maior, 2, ",", ".");
		$margem = ($c * 170 * $melhorAcumulo) + (100 * $c * $melhorAcumulo);
		$margemRs = number_format($margem, 2, ",", ".");
		echo "$c contratos: R$ $lucro  	- margem R$ $margemRs \n";
	}
	echo "\n";
}

print_r($ganhoScore);

echo "		";
foreach ($matrizAcumulo as $ac){
	echo "$ac	";
}
echo "| M\n";
echo "--------------------------";
foreach ($matrizAcumulo as $ac){
	echo "------";
}
echo "------\n";
foreach ($acumuloCompare as $dia => $dados){
	echo "$dia	";
	$media = 0;
	foreach ($dados as $ac => $pts){
		echo "$pts	";
		$media += $pts;
		if(empty($acumuloSoma[$ac])) $acumuloSoma[$ac] = 0;
		$acumuloSoma[$ac] += $pts;
	}
	$media /= count($matrizAcumulo);
	$media = number_format($media, 1, ".", "");
	echo "| $media\n";
}
echo "\n     		";
foreach ($acumuloSoma as $sm){
	$sm /= count($arquivos);
	$sm = number_format($sm, 1, ".", "");
	echo "$sm	";
}

// Relatorio:
// Compra de 2 contratos por vez (por conta)
// - acumulo de 12 contratos na mão (6 opers)
// - máximo de perda de 6pts
// - minímo de ganho de 2pts
// - operando a cada 5min
// - Media de 47pts por dia.
// - Media de 96pts por dia (6 contratos, 36 max ao mesmo tempo)
