<?php

require_once("Printable.php");
require_once("Trade.php");
require_once("Ordem.php");

class DayTrade extends Printable {

    var $trades = [];
    var $dados = [];
    var $ordensGeradas = [];

    var $minGanho = 0;
    var $maxPerda = 0;
    var $maxContratos = 0;

    var $ultimoPreco = 0;
    var $ultimaOperacao = false;
    var $fatorLucro = 0;
    var $lucroPts = 0;
    var $lucroRs = 0;

    var $gravarFile = "";
    var $nome = "";
    var $usandoTendencias = false;

    function nomear($str){
        $this->nome = $str;
        return $this;
    }

    function comTendencias(){
        $this->usandoTendencias = true;
        return $this;
    }

    function negociar($ativo, $acumulo, $alavancagem = 1){
        $tradeAlta = new Trade($ativo, "XP", $acumulo, $alavancagem);
        $tradeBaixa = new Trade($ativo, "Rico", $acumulo, $alavancagem);
        $tradeAlta->operarNa(Trade::ALTA);
        $tradeBaixa->operarNa(Trade::BAIXA);
        $this->trades[] = $tradeAlta;
        $this->trades[] = $tradeBaixa;
        $this->maxContratos = $acumulo;
        return $this;
    }

    function lucroPorContrato($lucro){
        $this->fatorLucro = $lucro;
        return $this;
    }

    function sugerirBaseadoEm($dados){
        $this->dados = $dados;
        return $this;
    }

    function arriscarNoMaximo($perder){
        $this->maxPerda = $perder;
        return $this;
    }

    function gravarResultadoEm($g){
        $this->gravarFile = $g;
        foreach($this->trades as $trade){
            $estrategia = strtolower($trade->estrategia);
            $arquivo = "$g-$estrategia.csv";
            gravarOperacaoNoArquivo($arquivo, $trade->operacoes, $this->ultimoPreco);
        }
        return $this;
    }

    function ganharAoMenos($ganhar){
        $this->minGanho = $ganhar;
        return $this;
    }

    function investir(){
        foreach ($this->dados as $min => $preco) {
            $this->ultimaOperacao = $k == count($dados) - 1;

            $regra = new Regra();
            $regra->ganho = $this->minGanho;
            $regra->perda = $this->maxPerda;
            $regra->acumulo = $this->maxContratos;
            $regra->momento = $min;
            $regra->ancoragem = ANCORAGEM;
            $regra->ultimaOperacao = $this->ultimaOperacao;

            foreach ($this->trades as $trade){
                $ordem = $this->pegaOrdemDo($trade, $preco);

                if ($this->ultimoPreco > 0){
                    $trade->calcularIndicadores($preco);
                    if ($trade->continuarOperando($preco)){
                        $encerradas = $trade->encerrarSe($preco, $regra);
                        $ordem->encerrar($encerradas);
                        $iniciadas = $trade->posicionarSe($preco, $regra);
                        $ordem->posicionar($iniciadas);
                    }
                    else {
                        $encerradas = $trade->encerrar($preco, $regra);
                        $ordem->encerrar($encerradas);
                    }
                }
                else {
                    $trade
                        ->posicionar($preco)
                        ->lembrarPreco($preco);
                }
            }

            $this->ultimoPreco = $preco;
        }
        return $this;
    }

    function pegaOrdemDo($trade, $preco){
        foreach($this->ordensGeradas as $ordem){
            if($ordem->ativo == $trade->ativo && $ordem->estrategia == $trade->estrategia){
                return $ordem;
            }
        }
        $ordem = new Ordem($trade->ativo, $trade->conta, $preco);
        $ordem->operarNa($trade->estrategia);
        if($this->usandoTabelas){
            $this->ordensGeradas[] = $ordem;
        }
        return $ordem;
    }

    function lucroEmPontos(){
        return $this->lucroPts;
    }
    function lucroEmReais(){
        return $this->lucroRs;
    }

    function relatar($encerrar = false){
        $r = [];
        $saldo = 0;
        $saldoParcial = 0;
        $fecharOperacao = $encerrar ? $this->ultimoPreco : 0;

        if($this->usandoTendencias){
            $r[] = $this->centralizaStr(empty($this->nome) ? "RESULTADO" : $this->nome);
            $r[] = $this->strDivisor(".");
            $tabs = tendenciaAbsoluta($this->dados);
        }

        foreach($this->trades as $trade){
            $report = $trade->report($fecharOperacao);
            if(!$this->usandoTendencias){
                $r[] = $report;
            } else {
                $r[] = "--> $trade->conta ($trade->estrategia): " . pts($trade->saldo());
            }
            $saldos += $trade->saldo();
            $saldoParcial += $trade->saldoParcial($this->ultimoPreco);
        }

        if($this->usandoTendencias){
            $r[] = "--> Tendencia: $tabs " . (
                $tabs > BOUNDS_TREND ? "+ Alta" :
                $tabs < -BOUNDS_TREND ? "- Baixa" :
                    ".. Neutra"
            );
        }
        else {
            $r[] = $this->pularLinha();
            $r[] = $this->centralizaStr(empty($this->nome) ? "TOTAL" : $this->nome);
        }

        $this->lucroPts = $saldos;
        $this->lucroRs = $saldos * $this->fatorLucro;

        $r[] = $this->strDivisor();
        $r[] = "Resultado:  " .pts($this->lucroPts). " pts";
        $r[] = "Lucro:      R$ " . money($this->lucroRs);

        if(!$this->usandoTendencias){
            $r[] = $this->strDivisor(".");
            $r[] = "Parcial:    R$ " . money($saldoParcial * $this->fatorLucro);
        }

        foreach($this->ordensGeradas as $ordem){
            $r[] = $ordem->report();
        }
        $r[] = $this->pularLinha();

        echo implode("\n", $r);
        return $this;
    }
}
