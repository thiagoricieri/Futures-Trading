<?php

include_once("lib/functions.php");
include_once("lib/Esquema.php");

$esquema = new Esquema();
$esquema
	->encerrarOperacao()
	->testarGanhos([0.5])
	->testarPerdas([50])
	->acumulandoNoMaximo([30])
	->usarDiretorio(DIR_ARQUIVOS)
	->usarHistorico(["set-19.txt"])
	->comTabelas()
	->simular();
