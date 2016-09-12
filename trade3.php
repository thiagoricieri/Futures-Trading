<?php

include_once("lib2.php");

define("C", "C");
define("V", "V");

$arquivos = [
	"set-12.txt"
];
$gerar = false;

$dirArquivos = "database/";
$dirOutput = "output/";

$contratos = 1;
$testeGanho = [2]; // 4
$testePerda = [40]; // 40
$testeAcumu = [40]; // 30

$maxPerdaDiaPts = 300; // 150
$triggerMaxPerdaAt = 1000;

$geralVendas = 0;
$geralCompras = 0;

$margem = -1;

$files = glob($dirOutput . "*");
foreach($files as $file){
	if(is_file($file))
		unlink($file);
}

foreach ($arquivos as $arq){
	$outputArq = str_replace(".txt", "", $arq);

	foreach($testeGanho as $ganho){

		foreach($testePerda as $perda){

			foreach ($testeAcumu as $acumulo) {

				$dados = read_data($dirArquivos . $arq);
				$dados = array_slice($dados, 0, 100);

				$ultimoPreco = 0;
				$tendencia = 0;
				$comprasVazias = 1;
				$vendasVazias = 1;

				$compras = [];
				$vendas = [];
				$ordens = [];

				$is_ultimo = false;
				$ultimaCompraXp = 0;
				$ultimaVendaXp = 0;
				$ultimaCompraRico = 0;
				$ultimaVendaRico = 0;

				foreach ($dados as $k=> $preco) {

					$is_ultimo = $k == count($dados) - 1;

					if($ultimoPreco > 0){

						// 1 alta, 1 baixa, 0 neutra
						$fator = 0;
						$fator = $ultimoPreco < $preco ? +1 : $fator;
						$fator = $ultimoPreco > $preco ? -1 : $fator;

						if($fator < 0) $tendencia--;
						else if($fator > 0) $tendencia++;
						else $tendencia = 0;

						$partialC = calcular($compras, $preco);
						$partialV = calcular($vendas, $preco);

						if(
								$partialC > -$maxPerdaDiaPts &&
								$min <= $triggerMaxPerdaAt
						){
							foreach($compras as $k=> $c){
								if(
									$c->venda == 0 &&  (
										$preco - $ganho >= $c->compra || 
										$preco + $perda <= $c->compra
									)
								){
									$compras[$k]->venda = $preco;
									$comprasVazias -= $c->multiplo;

									if($is_ultimo)
										$ultimaVendaXp += $c->multiplo;
								}
							}

							$multiploC = $tendencia > 0 ?
								($tendencia * 2) + 1 : 0;

							if($multiploC > 0 && $comprasVazias < $acumulo){
								$compras[] = new Operacao($preco, 0, $multiploC, $tendencia);
								$comprasVazias += $multiploC;

								if($is_ultimo)
									$ultimaCompraXp += $multiploC;
							}
						}
						else {
							foreach($compras as $k=> $c){
								if($c->venda == 0 ){
									$compras[$k]->venda = $preco;
									$comprasVazias -= $c->multiplo;

									if($is_ultimo)
										$ultimaVendaXp += $c->multiplo;
								}
							}
						}


						if(
								$partialV > -$maxPerdaDiaPts && 
								$min <= $triggerMaxPerdaAt
						){
							foreach($vendas as $k=> $v){
								if(
									$v->compra == 0 &&  (
										$preco + $ganho <= $v->venda || 
										$preco - $perda >= $v->venda
									)
								){
									$vendas[$k]->compra = $preco;
									$vendasVazias -= $v->multiplo;

									if($is_ultimo)
										$ultimaCompraRico += $v->multiplo;
								}
							}

							$multiploV = $tendencia < 0 ?
								($tendencia * 2) - 1 : 0;

							if($multiploV < 0 && $vendasVazias < $acumulo){
								$multiploV = abs($multiploV);
								$vendas[] = new Operacao(0, $preco, $multiploV, $tendencia);
								$vendasVazias += $multiploV;

								if($is_ultimo)
									$ultimaVendaRico += $multiploV;
							}
						}
						else {
							foreach($vendas as $k=> $v){
								if($v->compra == 0){
									$vendas[$k]->compra = $preco;
									$vendasVazias -= $v->multiplo;

									if($is_ultimo)
										$ultimaCompraRico += $v->multiplo;
								}
							}
						}

						if($is_ultimo){
							if($ultimaVendaXp > 0){
								$ordens[] = "- Venda $ultimaVendaXp cts na XP a $preco pts;";
							}
							if($ultimaVendaRico > 0){
								$ordens[] = "- Venda $ultimaVendaRico cts na Rico a $preco pts;";
							}
							if($ultimaCompraXp > 0){
								$ordens[] = "- Compre $ultimaCompraXp cts na XP a $preco pts;";
							}
							if($ultimaCompraRico > 0){
								$ordens[] = "- Compre $ultimaCompraRico cts na Rico a $preco pts;";
							}
						}
					}
					else {
						$compras[] = new Operacao($preco, 0);
						$vendas[] = new Operacao(0, $preco);
					}

					$ultimoPreco = $preco;
				}

				$outputCompras =
					$dirOutput . 
					$outputArq . 
					"-g$ganho-p$perda-a$acumulo-compras.csv";

				$outputVendas = 
					$dirOutput . 
					$outputArq . 
					"-g$ganho-p$perda-a$acumulo-vendas.csv";

				$fecharOperacao = 0;//$ultimoPreco;

				$somaCompras = write_oper_file(
					$outputCompras, $compras, $fecharOperacao, $gerar);
				$somaVendas = write_oper_file(
					$outputVendas, $vendas, $fecharOperacao, $gerar);

				$somaVendas = 0;
				$somaCompras = 0;
				$ctsVendas = 0;
				$ctsCompras = 0;
				$abeCompras = 0;
				$abeVendas = 0;

				echo "\n\n";
				echo "        XP\n";
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

				echo "       RICO";
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