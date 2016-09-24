<?php

include_once("lib/includes.php");
include_once("lib/functions.php");
include_once("lib/Esquema.php");

$esquema = new Esquema();
$comprados = 0;
$vendidos = 0;
$grandeLucro = 5;
$menorPreju = -5;
$trend = 1;

foreach($arquivos as $arq){

    $dados = lerDados(DIR_ARQUIVOS . $arq);
    echo "Testando $arq\n";
    echo "-----------------------\n";
    $precoCompra = 0;
    $precoVenda = 0;
    $comprado = false;
    $vendido = false;
    $lucroCompra = 0;
    $lucroVenda = 0;
    $tendencia = 0;
    $ultimoPreco = 0;

    foreach($dados as $minuto => $preco){
        if($esquema->indexEquivalente("09:00") <= $minuto){
            if($precoCompra == 0){
                $precoCompra = $preco;
                echo "Comprou a $preco\n";
                echo "...........\n";
            }
            if($precoVenda == 0){
                $precoVenda = $preco;
                echo "Vendeu a $preco\n";
                echo "...........\n";
            }
            if($preco >= $precoCompra + $grandeLucro && !$vendido && $precoCompra > 0){
                echo "Vendeu às " . $esquema->horaEquivalente($minuto) . " ($preco) = " . ($preco - $precoCompra) . "\n";
                $lucroVenda += $preco - $precoCompra;
                $vendido = true;
                $vendidos++;
            }
            if($preco <= $precoVenda - $grandeLucro && !$comprado && $precoVenda > 0){
                echo "Comprou às " . $esquema->horaEquivalente($minuto) . " ($preco) = " . ($precoVenda - $preco) . "\n";
                $lucroCompra += $precoVenda - $preco;
                $comprado = true;
                $comprados++;
            }
            if($vendido && !$comprado && $preco <= $precoVenda + $menorPreju){
                echo "Diminuindo prejuízo na baixa: " . $esquema->horaEquivalente($minuto) . " ($preco) = " . ($precoVenda - $preco) . "\n";
                $lucroCompra += $precoVenda - $preco;
                $comprado = true;
            }
            if($comprado && !$vendido && $preco >= $precoCompra - $menorPreju){
                echo "Diminuindo prejuízo na alta: " . $esquema->horaEquivalente($minuto) . " ($preco) = " . ($preco - $precoCompra) . "\n";
                $lucroVenda += $preco - $precoCompra;
                $vendido = true;
            }
        }
        if($ultimoPreco > 0){
            $tendencia = $ultimoPreco - $preco;
            echo "Trend " . number_format($tendencia, 1, ".", ",") . "\t" . str_repeat(".", abs($tendencia)) . "\n";
        }
        $ultimoPreco = $preco;
    }

    if(!$vendido && $precoCompra > 0){
        echo "Não consegui Vender. Dia acabou com ($preco) = " . ($preco - $precoCompra) . "\n";
        $lucroVenda += $preco - $precoCompra;
    }
    if(!$comprado && $precoVenda > 0){
        echo "Não consegui Comprar. Dia acabou com ($preco) = " . ($precoVenda - $preco) . "\n";
        $lucroCompra = $precoVenda - $preco;
    }
    echo "-----------------------\n\n";
}
$dias = count($arquivos);
echo "\n\nResultado\n";
echo "-----------------------\n";
echo "Baixa: $comprados de $dias = $lucroCompra \n";
echo "Alta: $vendidos de $dias = $lucroVenda \n";
echo "Total: " . ($lucroCompra + $lucroVenda) . "\n\n\n";
