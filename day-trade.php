<?php

include_once("lib/lib2.php");
include_once("lib/Regra.php");
include_once("lib/Operacao.php");
include_once("lib/Trade.php");
include_once("lib/Ordem.php");
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
				$tradeAlta = new Trade("WDOV16", "XP", $acumulo);
				$tradeBaixa = new Trade("WDOV16", "Rico", $acumulo);

				$tradeAlta->operarNa(Trade::ALTA);
				$tradeBaixa->operarNa(Trade::BAIXA);

				$ultimoPreco = 0;
				$ultimo = false;

				foreach ($dados as $k=> $preco) {

					$ordensAlta = new Ordem("WDOV16", "XP", $preco);
					$ordensBaixa = new Ordem("WDOV16", "Rico", $preco);

					$ultimo = $k == count($dados) - 1;

					$regra = new Regra();
					$regra->ganho = $ganho;
					$regra->perda = $perda;
					$regra->acumulo = $acumulo;
					$regra->momento = $min;
					$regra->ancoragem = ANCORAGEM;
					$regra->ultimaOperacao = $ultimo;

					if($ultimoPreco > 0){

						$tradeAlta->calcularIndicadores($preco);
						$tradeBaixa->calcularIndicadores($preco);

						if($tradeAlta->continuarOperando($preco)){
							$encerradas = $tradeAlta->encerrarSe($preco, $regra);
							$ordensAlta->vender($encerradas);
							$iniciadas = $tradeAlta->posicionarSe($preco, $regra);
							$ordensAlta->comprar($iniciadas);
						}
						else {
							$encerradas = $tradeAlta->encerrar($preco, $regra);
							$ordensAlta->vender($encerradas);
						}

						if($tradeBaixa->continuarOperando($preco)){
							$encerradas = $tradeBaixa->encerrarSe($preco, $regra);
							$ordensBaixa->comprar($encerradas);
							$iniciadas = $tradeBaixa->posicionarSe($preco, $regra);
							$ordensBaixa->vender($iniciadas);
						}
						else {
							$encerradas = $tradeBaixa->encerrar($preco, $regra);
							$ordensBaixa->comprar($encerradas);
						}
					}
					else {
						$tradeAlta
							->posicionar($preco)
							->lembrarPreco($preco);
						$tradeBaixa
							->posicionar($preco)
							->lembrarPreco($preco);
					}

					$ultimoPreco = $preco;
				}

				// -----------
				// OUTPUT
				// -----------

				$fecharOperacao = $encerrarOperacao ? $ultimoPreco : 0;

				echo $tradeAlta->report($fecharOperacao);
				echo $tradeBaixa->report($fecharOperacao);

				echo "       TOTAL ($ultimoPreco)\n";
				echo "--------------------\n";
				echo "Resultado: " .pts($tradeAlta->saldo() + $tradeBaixa->saldo()). " pts\n";
				echo "\n\n";

				echo $ordensAlta->report();
				echo $ordensBaixa->report();
				echo "\n\n";
			}
		}
	}
}
