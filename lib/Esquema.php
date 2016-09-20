<?php

require_once("DayTrade.php");
include_once("Regra.php");
include_once("Operacao.php");
include_once("Trade.php");
include_once("Ordem.php");
include_once("includes.php");

class Esquema {

    var $ganhos = [];
    var $perdas = [];
    var $acumulos = [];
    var $baseDados = [];

    var $trades = [];

    var $gerandoCsvs = false;
    var $encerrar = false;

    var $usandoTendencias = false;
    var $usandoTabelas = false;

    var $dirDados = "";
    var $dirOutput = "";

    function gerarArquivos(){
        $this->gerandoCsvs = true;
        return $this;
    }

    function gravarResultados($gerar){
        $this->gerarArquivos = $gerar;
        return $this;
    }

    function comTabelas(){
        $this->usandoTabelas = true;
        return $this;
    }

    function comTendencias(){
        $this->usandoTendencias = true;
        return $this;
    }

    function encerrarOperacao(){
        $this->encerrar = true;
        return $this;
    }

    function testarGanhos($arr){
        $this->ganhos = $arr;
        return $this;
    }

    function testarPerdas($arr){
        $this->perdas = $arr;
        return $this;
    }

    function acumulandoNoMaximo($ac){
        $this->acumulos = $ac;
        return $this;
    }

    function usarDiretorio($dir){
        $this->dirDados = $dir;
        return $this;
    }

    function usarOutput($dir){
        $this->dirOutput = $dir;
        return $this;
    }

    function usarHistorico($dados){
        $this->baseDados = $dados;
        return $this;
    }

    function simular(){

        if($this->gerandoCsvs){
            removeArquivosOutput($this->dirOutput);
        }

        $lucroAcumuladoPts = 0;
        $lucroAcumuladoRs = 0;

        foreach($this->baseDados as $arq){
        	$outputArq = str_replace(".txt", "", $arq);

            foreach($this->ganhos as $ganho){
                foreach($this->perdas as $perda){
                    foreach($this->acumulos as $acumulo){

                        $dados = lerDados($this->dirDados . $arq);
                        $dayTrade = new DayTrade();

                        if($this->usandoTabelas){
                            $dayTrade
            					->arriscarNoMaximo($perda)
            					->ganharAoMenos($ganho)
            					->sugerirBaseadoEm($dados)
            					->negociar("WDOV16", $acumulo)
                                ->lucroPorContrato(10)
            					->arriscarNoMaximo($perda)
            					->ganharAoMenos($ganho)
            					->sugerirBaseadoEm($dados)
            					->investir()
            					->relatar($this->encerrar);
                        }

                        if($this->usandoTendencias){
                            $outDir = DIR_OUTPUT;
                            $outDir.= $outputArq;
                            $outDir.= "-g$ganho-p$perda-a$acumulo";

                            $dayTrade
                                ->nomear($arq)
                                ->arriscarNoMaximo($perda)
                                ->ganharAoMenos($ganho)
                                ->sugerirBaseadoEm($dados)
                                ->lucroPorContrato(10)
                                ->negociar("WDOV16", $acumulo)
                                ->arriscarNoMaximo($perda)
                                ->ganharAoMenos($ganho)
                                ->sugerirBaseadoEm($dados)
                                ->comTendencias()
                                ->investir()
                                ->relatar($this->encerrar);

                            if($this->gerandoCsvs){
                                $dayTrade->gravarResultadoEm($outDir);
                            }
                        }

                        $lucroAcumuladoPts += $dayTrade->lucroEmPontos();
                        $lucroAcumuladoRs += $dayTrade->lucroEmReais();
                        $this->trades[] = $dayTrade;
                    }
                }
            }
        }

        if($this->usandoTendencias){
            $dias = count($this->baseDados);
            $media = pts($lucroAcumuladoPts / $dias);
            $lucroPts = pts($lucroAcumuladoPts);
            $lucroRs = money($lucroAcumuladoRs);

            echo "====================\n";
            echo "GERAL \n";
            echo "$lucroPts pts ($media em $dias) = R$ $lucroRs";
            echo "\n\n";
        }
    }
}
