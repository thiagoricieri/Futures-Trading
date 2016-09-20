<?php

include_once("lib/lib2.php");
include_once("lib/Esquema.php");

$esquema = new Esquema();
$esquema
	->encerrarOperacao()
	->testarGanhos([0.5])
	->testarPerdas([50])
	->acumulandoNoMaximo([5])
	->usarDiretorio(DIR_ARQUIVOS)
	->usarHistorico(["set-19.txt"])
	->comTabelas()
	->simular();
