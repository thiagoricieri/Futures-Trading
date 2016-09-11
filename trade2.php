<?php

include_once("lib2.php");

define("C", "C");
define("V", "V");

$arquivos = [
	"ago-22.txt", "ago-23.txt", "ago-24.txt", "ago-25.txt",
	"ago-26.txt", "ago-29.txt", "ago-30.txt", "ago-31.txt",
	"set-1.txt", "set-2.txt", "set-5.txt", "set-6.txt", 
	"set-8.txt", "set-9.txt"
];
$gerar = true;

$dirArquivos = "database/";
$dirOutput = "output/";

$contratos = 1;
$testeGanho = [2];
$testePerda = [10];
$testeAcumu = [30];

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

				foreach ($dados as $preco) {

					if($ultimoPreco > 0){

						// 1 alta, 1 baixa, 0 neutra
						$fator = $ultimoPreco < $preco ? 1 : -1;
						$fator = $ultimoPreco == $preco ? 0 : $fator;

						if($fator < 0) $tendencia--;
						else if($fator > 0) $tendencia++;
						else $tendencia = 0;

						foreach($compras as $k=> $c){
							if(
								$c->venda == 0 &&  (
									$preco - $ganho >= $c->compra || 
									$preco + $perda <= $c->compra
								)
							){
								$compras[$k]->venda = $preco;
								$comprasVazias -= $c->multiplo;
							}
						}
						foreach($vendas as $k=> $v){
							if(
								$v->compra == 0 &&  (
									$preco + $ganho <= $v->venda || 
									$preco - $perda >= $v->venda
								)
							){
								$vendas[$k]->compra = $preco;
								$vendasVazias -= $v->multiplo;
							}
						}

						$multiploC = ($tendencia * 2) + 1;
						$multiploV = ($tendencia * 2) - 1;

						if($multiploC > 0 && $comprasVazias < $acumulo){
							$compras[] = new Operacao($preco, 0, $multiploC, $tendencia);
							$comprasVazias += $multiploC;
						}

						if($multiploV < 0 && $vendasVazias < $acumulo){
							$multiploV = abs($multiploV);
							$vendas[] = new Operacao(0, $preco, $multiploV, $tendencia);
							$vendasVazias += $multiploV;
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

				$somaCompras = write_oper_file(
					$outputCompras, $compras, $ultimoPreco, $gerar);
				$somaVendas = write_oper_file(
					$outputVendas, $vendas, $ultimoPreco, $gerar);

				$cne = count($compras);
				$vne = count($vendas);
				$lucro = number_format(($somaCompras + $somaVendas) * $contratos * 10, 2, ",", ".");

				echo "\n";
				echo "RESULTADO $arq ($ganho, $perda, $acumulo)\n";
				echo "--> Compras: $somaCompras ($cne negocios)\n";
				echo "--> Vendas: $somaVendas ($vne negocios)\n";
				echo "    --------------\n";
				echo "    " .($somaCompras + $somaVendas). " = R$ $lucro\n";
				echo "\n";

				$geralCompras += $somaCompras;
				$geralVendas += $somaVendas;
			}
		}
	}
}

$dias = count($arquivos);
$geralResult = $geralVendas + $geralCompras;
$geralMedia = number_format($geralResult / $dias, 2, ",", ".");
$geralLucro = number_format($geralResult * $contratos * 10, 2, ",", ".");

echo "====================\n";
echo "GERAL \n";
echo "--> Compras: $geralCompras\n";
echo "--> Vendas: $geralVendas\n";
echo "    -------------\n";
echo "    $geralResult ($geralMedia em $dias) = R$ $geralLucro\n";
echo "\n";