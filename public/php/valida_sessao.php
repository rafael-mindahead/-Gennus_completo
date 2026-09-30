<?php 
// php/valida_sessao.php
session_start();

if (isset($_SESSION['usuario'])) {
    $retorno = [
        'status' => 'ok', 
        'mensagem' => '', 
        'data' => $_SESSION['usuario']
    ];
} else {
    $retorno = [
        'status' => 'nok', 
        'mensagem' => 'Acesso negado', 
        'data' => []
    ];
}

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);