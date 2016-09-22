<?php

$miniIndice = [
    
];

foreach($miniIndice as $ind){
    $arq = file("ativos/win/$ind");
    $norm = [];
    $last2 = "";

    foreach($arq as $i){
        $i = trim($i);
        if(strlen($i) == 5) {
            $norm[] = $i;
            $last2 = substr($i, 0, 2);
        }
        else if(strlen($i) == 3){
            $norm[] = "$last2$i";
        }
    }

    $win = implode("\n", $norm);
    $fp = fopen("ativos/win/$ind", "w+");
    fwrite($fp, $win);
    fclose($fp);
}
