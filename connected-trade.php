<?php

include_once("lib/functions.php");
include_once("lib/Esquema.php");
include_once("integracao/Rico.php");
include_once("integracao/XP.php");

$esquema = new Esquema();
$esquema
	->testarGanhos([0.5])
	->testarPerdas([50])
	->prejuizoMaximo(420)
	->acumulandoNoMaximo([30])
	->usarDiretorio(DIR_ARQUIVOS)
	->usarHistorico(["hoje.txt"])
	->comTabelas()
	->simular();
