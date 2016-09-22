<?php

include_once("lib/functions.php");
include_once("lib/Esquema2.php");

$esquema = new Esquema();
$wdo = new Ativo("Mini-Dólar", "WDO", 10);
$win = new Ativo("Mini-Índice", "WIN", 0.2);

$h = new Hipotese();
$wdo->buscarDadosEm("wdo")
	->hipotese($h
		->acumular(30)
		->tentarGanhar(0.5)
		->alavancar(1)
		->aguentarPerder(50)
		->pararSePrejuizoFor(420));

$h = new Hipotese();
$win->buscarDadosEm("win")
	->hipotese($h
		->acumular(60)
		->tentarGanhar(100)
		->alavancar(1)
		->aguentarPerder(200)
		->pararSePrejuizoFor(420));

$esquema
	->encerrarOperacao()
	// ->gerarArquivos()
	->usarDiretorio(DIR_ARQUIVOS)
	->usarOutput(DIR_OUTPUT)
	->usarHistorico($arquivos)
	->negociar($win)
	->negociar($wdo)
	->comTendencias()
	->simular()
	->relatarMaiorPrejuizo()
	->relatarMaiorLucro();
