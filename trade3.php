<?php

include_once("lib/lib2.php");
include_once("lib/Operacao.php");
include_once("lib/includes.php");

$arquivos = ["hoje.txt"];

// --------
// CONFIG
// --------

$encerrarOperacao = false;
$testeGanho = [0.5];
$testePerda = [50];
$testeAcumu = [30];

// -------
// SCRIPT
// -------

$geralVendas = 0;
$geralCompras = 0;

foreach ($arquivos as $arq){

	foreach($testeGanho as $ganho){
		foreach($testePerda as $perda){
			foreach ($testeAcumu as $acumulo) {

				$dados = read_data(DIR_ARQUIVOS . $arq);

				$ultimoPreco = 0;
				$ultimaTendencia = 0;

				$tendencia = 0;
				$comprasVazias = 1;
				$vendasVazias = 1;

				$ordens = [];
				$compras = [];
				$vendas = [];
				$tempsrc = [];

				$is_ultimo = false;
				$ultimaCompraXp = 0;
				$ultimaVendaXp = 0;
				$ultimaCompraRico = 0;
				$ultimaVendaRico = 0;

				foreach ($dados as $k=> $preco) {
					$tempsrc[] = $preco;
					$is_ultimo = $k == count($dados) - 1;

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

									if($is_ultimo)
										$ultimaVendaXp += $c->multiplo;
								}
							}

							$m = $tabs < -BOUNDS_TREND ? ceil(abs($tabs)) : 1;
							$multiploC = $tendencia > 0 ?
								($tendencia * 2) + 1 + $m - $boost : 1;

							if($multiploC > 0 && $comprasVazias < $acumulo){
								$oper = new Operacao($preco, 0, $multiploC, $tabs);
								$oper->indexInicio = $preco;
								$compras[] = $oper;
								$comprasVazias += $multiploC;

								if($is_ultimo)
									$ultimaCompraXp += $multiploC;
							}
						}
						else {
							foreach($compras as $k=> $c){
								if($c->venda == 0 ){
									$compras[$k]->venda = $preco;
									$compras[$k]->indexFim = $min;
									$comprasVazias -= $c->multiplo;

									if($is_ultimo)
										$ultimaVendaXp += $c->multiplo;
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
									$vendas[$k]->indexInicio = $min;
									$vendasVazias -= $v->multiplo;

									if($is_ultimo)
										$ultimaCompraRico += $v->multiplo;
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

								if($is_ultimo)
									$ultimaVendaRico += $multiploV;
							}
						}
						else {
							foreach($vendas as $k=> $v){
								if($v->compra == 0){
									$vendas[$k]->compra = $preco;
									$vendas[$k]->indexFim = $min;
									$vendasVazias -= $v->multiplo;

									if($is_ultimo)
										$ultimaCompraRico += $v->multiplo;
								}
							}
						}

						if($is_ultimo){
							if($ultimaVendaXp > 0){
								$ordens[] = "- Venda $ultimaVendaXp cts na Rico a $preco pts;";
							}
							if($ultimaVendaRico > 0){
								$ordens[] = "- Venda $ultimaVendaRico cts na XP a $preco pts;";
							}
							if($ultimaCompraXp > 0){
								$ordens[] = "- Compre $ultimaCompraXp cts na Rico a $preco pts;";
							}
							if($ultimaCompraRico > 0){
								$ordens[] = "- Compre $ultimaCompraRico cts na XP a $preco pts;";
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

				$fecharOperacao = $encerrarOperacao ? $ultimoPreco : 0;

				$somaCompras = calcular($compras, $fecharOperacao, $encerrarOperacao);
				$somaVendas = calcular($vendas, $fecharOperacao, $encerrarOperacao);

				$somaVendas = 0;
				$somaCompras = 0;
				$ctsVendas = 0;
				$ctsCompras = 0;
				$abeCompras = 0;
				$abeVendas = 0;

				echo "\n\n";
				echo "        RICO\n";
				echo "--------------------\n";
				echo "COMPRA	CTS	VENDA\n";
				foreach ($compras as $c){
					echo "$c->compra	x $c->multiplo	$c->venda\n";
					if($c->venda > 0){
						$somaCompras += $c->diferenca();
						$ctsCompras += $c->multiplo;
					}
					else {
						$abeCompras += $c->multiplo;
					}
				}
				echo "--------------------\n";
				echo "Fechados: $ctsCompras cts ($somaCompras pts)\n";
				echo "Abertos: $abeCompras cts (lim. $acumulo)\n";
				echo "\n\n";

				echo "       XP";
				echo "\n--------------------\n";
				echo "VENDA	CTS	COMPRA\n";
				foreach($vendas as $v){
					echo "$v->venda	x $v->multiplo	$v->compra\n";
					if($v->compra > 0){
						$somaVendas += $v->diferenca();
						$ctsVendas += $v->multiplo;
					}
					else {
						$abeVendas += $v->multiplo;
					}
				}
				echo "--------------------\n";
				echo "Fechados: $ctsVendas cts ($somaVendas pts)\n";
				echo "Abertos: $abeVendas cts (lim. $acumulo)\n";
				echo "\n\n";

				echo "       TOTAL ($ultimoPreco)\n";
				echo "--------------------\n";
				echo "Resultado: " .pts($somaVendas + $somaCompras). " pts\n";
				echo "\n\n";

				echo "     ORDENS ($tendencia)";
				echo "\n--------------------\n";
				foreach($ordens as $ordem){
					echo $ordem . "\n";
				}
				echo "\n\n";
			}
		}
	}
}
