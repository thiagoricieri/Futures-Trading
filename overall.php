<?php

include_once("lib/functions.php");
include_once("lib/Esquema.php");

$esquema = new Esquema();
$esquema
	->encerrarOperacao(true)
	->gerarArquivos()
	->testarGanhos([0.5])
	->testarPerdas([50])
	->acumulandoNoMaximo([5])
	->usarDiretorio(DIR_ARQUIVOS)
	->usarOutput(DIR_OUTPUT)
	->usarHistorico($arquivos)
	->comTendencias()
	->simular();
