<?php

include_once("lib/lib2.php");
include_once("lib/Operacao.php");
include_once("lib/includes.php");

// --------
// CONFIG
// --------

$gerarArquivos = true;
$testeGanho = [0.5];
$testePerda = [50];
$testeAcumu = [5];

// -------
// SCRIPT
// -------

$geralVendas = 0;
$geralCompras = 0;

remove_arquivos_output(DIR_OUTPUT);

foreach ($arquivos as $arq){
	$outputArq = str_replace(".txt", "", $arq);

	foreach($testeGanho as $ganho){
		foreach($testePerda as $perda){
			foreach ($testeAcumu as $acumulo) {

				$dados = read_data(DIR_ARQUIVOS . $arq);
				// $dados = array_slice($dados, 30);

				$ultimoPreco = 0;
				$ultimaTendencia = 0;

				$tendencia = 0;
				$comprasVazias = 1;
				$vendasVazias = 1;

				$compras = [];
				$vendas = [];
				$tempsrc = [];

				foreach ($dados as $min => $preco) {
					$tempsrc[] = $preco;
					$more = (3 - ceil($min/48)) * 0.5;
					// $ganho = $ganho2 + $more;

					if($ultimoPreco > 0){

						// 1 alta, 1 baixa, 0 neutra
						$fator = 0;
						$fator = $ultimoPreco < $preco ? +1 : $fator;
						$fator = $ultimoPreco > $preco ? -1 : $fator;
						$diff = abs($ultimoPreco - $preco);

						if($fator < 0) $tendencia--;
						else if($fator > 0) $tendencia++;
						else $tendencia = 0;

						$tabs = $preco - $ultimoPreco;//tendencia_absoluta($tempsrc);
						$forca = ceil($tabs / BOUNDS_TREND);
						// echo ($tabs > 2 ? "+ Alta" : ($tabs < -2 ? "- Baixa" : ". Neu")) . "	$tabs\n";
						$ultimaTendencia = $tabs;

						$partialC = calcular($compras, $preco);
						$partialV = calcular($vendas, $preco);

						$media3 = media_movel($tempsrc, 2);
						$media21 = media_movel($tempsrc, 12);
						$boost = 0;//ceil(($media3 - $media21)/4);

						if(
								$partialC > -MAX_PERDA_DIA_PTS &&
								$min <= MAX_PERDA_DIA_TRIGGER
						){
							foreach($compras as $k=> $c){
								if(
									$c->venda == 0 && (
										$preco - $ganho >= $c->compra ||
										(
											$preco + $perda <= $c->compra &&
											$c->indexInicio + ANCORAGEM <= $min
										)
									)
								){
									$compras[$k]->venda = $preco;
									$compras[$k]->indexFim = $min;
									$comprasVazias -= $c->multiplo;
								}
							}

							$m = $tabs < -BOUNDS_TREND ? ceil(abs($tabs)) : 1;
							$multiploC = $tendencia > 0 ?
								($tendencia * 2) + 1 + $m - $boost : 1;

							if($multiploC > 0 && $comprasVazias < $acumulo){
								$oper = new Operacao($preco, 0, $multiploC, $tabs);
								$oper->indexInicio = $min;
								$compras[] = $oper;
								$comprasVazias += $multiploC;
							}
						}
						else {
							foreach($compras as $k=> $c){
								if($c->venda == 0 ){
									$compras[$k]->venda = $preco;
									$compras[$k]->indexFim = $min;
									$comprasVazias -= $c->multiplo;
								}
							}
						}


						if(
								$partialV > -MAX_PERDA_DIA_PTS &&
								$min <= MAX_PERDA_DIA_TRIGGER
						){
							foreach($vendas as $k=> $v){
								if(
									$v->compra == 0  && (
										$preco + $ganho <= $v->venda ||
										(
											$preco - $perda >= $v->venda &&
											$v->indexInicio + ANCORAGEM <= $min
										)
									)
								){
									$vendas[$k]->compra = $preco;
									$vendas[$k]->indexFim = $min;
									$vendasVazias -= $v->multiplo;
								}
							}

							$m = $forca > BOUNDS_TREND ? ceil($tabs) : 1;
							$multiploV = $tendencia < 0 ?
								($tendencia * 2) - 1 - $m + $boost: 1;

							if($multiploV < 0 && $vendasVazias < $acumulo){
								$multiploV = abs($multiploV);
								$oper = new Operacao(0, $preco, $multiploV, $tabs);
								$oper->indexInicio = $min;
								$vendas[] = $oper;
								$vendasVazias += $multiploV;
							}
						}
						else {
							foreach($vendas as $k=> $v){
								if($v->compra == 0){
									$vendas[$k]->compra = $preco;
									$vendas[$k]->indexFim = $min;
									$vendasVazias -= $v->multiplo;
								}
							}
						}
					}
					else {
						$compras[] = new Operacao($preco, 0);
						$vendas[] = new Operacao(0, $preco);
					}

					$ultimoPreco = $preco;
				}

				// -----------
				// OUTPUT
				// -----------

				$dirout = DIR_OUTPUT . $outputArq . "-g$ganho-p$perda-a$acumulo";
				$outputCompras = "$dirout-compras.csv";
				$outputVendas = "$dirout-vendas.csv";

				$somaCompras = calcular($compras, $ultimoPreco);
				$somaVendas = calcular($vendas, $ultimoPreco);

				if($gerarArquivos){
					$somaCompras = write_oper_file($outputCompras, $compras, $ultimoPreco);
					$somaVendas = write_oper_file($outputVendas, $vendas, $ultimoPreco);
				}

				$cne = count($compras);
				$vne = count($vendas);
				$lucro = money(($somaCompras + $somaVendas) * CONTRATOS * 10);
				$tabs = tendencia_absoluta($dados);

				echo "\n";
				echo "RESULTADO $arq ($ganho, $perda, $acumulo)\n";
				echo "--> Alta (XP): $somaCompras ($cne negocios)\n";
				echo "--> Baixa (Rico): $somaVendas ($vne negocios)\n";
				echo "--> Tendencia do dia: $tabs = " .
					($tabs > BOUNDS_TREND ? "+ Alta" : ($tabs < -BOUNDS_TREND ? "- Baixa" : ".. Neutra")) . "\n";
				echo "    --------------\n";
				echo "    " .($somaCompras + $somaVendas). " = R$ $lucro\n";
				echo "\n";

				$geralCompras += $somaCompras;
				$geralVendas += $somaVendas;
			}
		}
	}
}

// -----------
// OUTPUT
// -----------

$dias = count($arquivos);
$geralResult = $geralVendas + $geralCompras;
$geralMedia = pts($geralResult / $dias);
$geralLucro = money($geralResult * CONTRATOS * 10);

echo "====================\n";
echo "GERAL \n";
echo "--> Alta (XP): $geralCompras\n";
echo "--> Baixa (Rico): $geralVendas\n";
echo "    -------------\n";
echo "    $geralResult ($geralMedia em $dias) = R$ $geralLucro\n";
echo "\n";
