<?php

class Credenciais {

    var $login;
    var $senha;
    var $assinatura;

    var $servicoUrl;
    var $urlEnviarOrdem;
    var $urlCancelarOrdem;
    var $urlListarOrdens;
    var $urlQuotacao;
    var $urlLogin;

    function enviarOrdemUrl(){
        return $this->servicoUrl . $this->urlEnviarOrdem;
    }

    function loginUrl(){
        return $this->servicoUrl . $this->urlLogin;
    }

    function listarOrdensUrl(){
        return $this->servicoUrl . $this->urlListarOrdens;
    }

    function cancelarOrdemUrl(){
        return $this->servicoUrl . $this->urlCancelarOrdem;
    }

    function quotarUrl(){
        return $this->servicoUrl . $this->urlQuotacao;
    }
}

class CredenciaisXP extends Credenciais {

    var $sessionId;
    var $csrftoken;
    var $jsessionId;
    var $tabInfo;
}

class CredenciaisRico extends Credenciais {


}
