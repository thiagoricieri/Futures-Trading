<?php

require_once("Print.php");

class Trade extends Printable {

    const ALTA = "Alta";
    const BAIXA = "Baixa";

    var $ativo = "";
    var $conta = "";
    var $maxAcumulo = 0;
    var $alavancagem = 1;
    var $operacoes = [];
    var $estrategia = "";
    var $operacoesVazias = 0;

    // Indicadores
    var $forca = 0;
    var $fator = 0;
    var $tendencia = 0;
    var $historico = [];

    // Private
    var $fechados = 0;
    var $abertos = 0;
    var $saldoParcial = 0;
    var $saldo = 0;

    function __construct($ativo, $conta, $acumulo, $alavancagem=1){
        $this->ativo = $ativo;
        $this->conta = $conta;
        $this->maxAcumulo = $acumulo;
        $this->alavancagem = $alavancagem;
    }

    function posicionar($preco, $qtd = 1){
        $qtd *= $this->alavancagem;
        if($this->estrategia == self::ALTA){
            $this->operacoes[] = new Operacao($preco, 0, $qtd);
        } else {
            $this->operacoes[] = new Operacao(0, $preco, $qtd);
        }
        $this->operacoesVazias += $qtd;
        return $this;
    }

    function encerrar($preco, $regra){
        $encerradas = 0;
        foreach($this->operacoes as $k=> $c){
            if($this->estrategia == self::ALTA){
                if($c->venda == 0){
                    $this->operacoes[$k]->venda = $preco;
                    $this->operacoes[$k]->indexFim = $regra->momento;
                    $this->operacoesVazias -= $c->multiplo;

                    if($regra->ultimaOperacao)
                        $encerradas += $c->multiplo;
                }
            }
            else {
                if($c->compra == 0){
                    $this->operacoes[$k]->compra = $preco;
                    $this->operacoes[$k]->indexFim = $regra->momento;
                    $this->operacoesVazias -= $c->multiplo;

                    if($regra->ultimaOperacao)
                        $encerradas += $c->multiplo;
                }
            }
        }
        return $encerradas;
    }

    function encerrarSe($preco, $regra){
        $encerradas = 0;
        foreach($this->operacoes as $k=> $c){
            if($this->estrategia == self::ALTA){
                if(
                    $c->venda == 0 && (
                        $preco - $regra->ganho >= $c->compra || (
                            $preco + $regra->perda <= $c->compra &&
                            $c->indexInicio + $regra->ancoragem <= $regra->momento
                        )
                    )
                ){
                    $this->operacoes[$k]->venda = $preco;
                    $this->operacoes[$k]->indexFim = $regra->momento;
                    $this->operacoesVazias -= $c->multiplo;
                    if($regra->ultimaOperacao)
                        $encerradas += $c->multiplo;
                }
            }
            // BAIXA
            else {
                if(
                    $c->compra == 0  && (
                        $preco + $ganho <= $c->venda || (
                            $preco - $regra->perda >= $c->venda &&
                            $c->indexInicio + $regra->ancoragem <= $regra->momento
                        )
                    )
                ){
                    $this->operacoes[$k]->compra = $preco;
                    $this->operacoes[$k]->indexInicio = $regra->momento;
                    $this->operacoesVazias -= $c->multiplo;
                    if($regra->ultimaOperacao)
                        $encerradas += $c->multiplo;
                }
            }
        }
        return $encerradas;
    }

    function calcularMultiplo(){
        $multiplo = 0;
        if($this->estrategia == self::ALTA){
            $boost = $this->forca < -4 ? ceil(abs($this->forca)) : 1;
            $multiplo = $this->tendencia > 0 ?
                ($this->tendencia * 2) + 1 + $boost : 1;
        }
        else {
            $boost = $this->forca > 4 ? ceil($this->forca) : 1;
            $multiplo = $this->tendencia < 0 ?
                ($this->tendencia * 2) - 1 - $boost : 1;
        }
        return $multiplo;
    }

    function posicionarSe($preco, $regra){
        $multiplo = $this->calcularMultiplo();
        $multiplo = abs($multiplo);
        $iniciadas = 0;

        if($multiplo > 0 && $this->operacoesVazias < $regra->acumulo){
            if($this->estrategia == self::ALTA){
                $oper = new Operacao($preco, 0, $multiplo, $this->forca);
            } else {
                $oper = new Operacao(0, $preco, $multiplo, $this->forca);
            }
            $oper->indexInicio = $regra->momento;
            $this->operacoes[] = $oper;
            $this->operacoesVazias += $multiplo;

            if($regra->ultimaOperacao)
                $iniciadas += $multiplo;
        }
        return $iniciadas;
    }

    function lembrarPreco($preco){
        $this->historico[] = $preco;
    }
    function operarNa($estrategia){
        $this->estrategia = $estrategia;
    }

    function calcularIndicadores($preco){
        $ultimoPreco = $this->ultimoPreco();
        $this->lembrarPreco($preco);

        $this->fator = 0;
        $this->fator = $ultimoPreco < $preco ? +1 : $this->fator;
        $this->fator = $ultimoPreco > $preco ? -1 : $this->fator;
        $diff = $preco - $ultimoPreco;

        if($this->fator < 0) $this->tendencia--;
        else if($this->fator > 0) $this->tendencia++;
        else $this->tendencia = 0;

        $this->forca = ceil($diff / 4);
    }

    function continuarOperando($preco){
        return $this->saldoParcial($preco) > -MAX_PERDA_DIA_PTS;
    }

    function report($fechamento=0) {
        $r = ["\n"];
        $soma = 0;
        $somaFechamento = 0;
        $fechados = 0;
        $abertos = 0;

        $r[] = $this->centralizaStr(strtoupper($this->conta));
        $r[] = $this->strDivisor();
        $r[] = $this->grade(
            "CTS",
            "COMPRA",
            "VENDA"
        );

        foreach($this->operacoes as $k => $oper){
            if($fechamento > 0){
                if($oper->venda > 0) $venda = $oper->venda;
                else $venda = $fechamento;

                if($oper->compra > 0) $compra = $oper->compra;
                else $compra = $fechamento;

                $oper->venda = $venda;
                $oper->compra = $compra;

                $somaFechamento += ($venda - $compra) * $oper->multiplo;
            }
            $r[] = $this->grade(
                $oper->multiplo,
                $oper->compra,
                $oper->venda
            );
            if($oper->venda > 0 && $oper->compra > 0){
                $soma += $oper->diferenca();
                $fechados += $oper->multiplo;
            }
            else {
                $abertos += $oper->multiplo;
            }
        }

        $pts = pts($soma);
        $r[] = $this->strDivisor();
        $r[] = "Fechados: $fechados cts ($pts pts)";
        $r[] = "Abertos: $abertos cts (lim. $this->maxAcumulo)";

        $this->abertos = $abertos;
        $this->fechados = $fechados;
        $this->saldo = $soma;

        if($somaFechamento > 0){
            $r[] = $this->strDivisor(".");
            $r[] = "Saldo parcial: " . pts($somaFechamento) . " pts";
            $this->saldoParcial = $somaFechamento;
        }

        return implode("\n", $r);
    }

    function ultimoPreco(){
        return $this->historico[count($this->historico) - 1];
    }
    function contratosAbertos(){
        return $this->abertos;
    }
    function contratosFechados(){
        return $this->fechados;
    }
    function saldo(){
        return $this->saldo;
    }
    function saldoParcial($precoAtual){
        $this->saldoParcial = 0;
    	foreach ($this->operacoes as $i){
    		$compra = $i->compra;
    		$venda = $i->venda;

    		if($compra < 1) $compra = $precoAtual;
    		if($venda < 1) $venda = $precoAtual;

    		$this->saldoParcial += ($venda - $compra) * $i->multiplo;
    	}
        return $this->saldoParcial;
    }

    private function grade($cts, $compra, $venda){
        return
            $this->fit($cts) .
            $this->fit($compra) .
            $this->fit($venda);
    }
    private function fit($value, $tabs = 1){
        $len = strlen("$value");
        return
            "$value" .
            str_repeat("\t", $tabs);
    }
}
