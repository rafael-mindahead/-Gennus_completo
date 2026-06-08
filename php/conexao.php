<?php // php/conexao.php
$servidor = 'localhost:3307';
$usuario = 'root';
$senha = ''; // vazia no XAMPP padrão
$nome_banco = 'gennus_database';

$conexao = new mysqli($servidor, $usuario, $senha, $nome_banco);

if ($conexao->connect_error) {
    header('Content-type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'nok',
        'mensagem' => 'Falha na conexao: ' . $conexao->connect_error,
        'data' => []
    ]);
    exit;
}
$conexao->set_charset('utf8mb4');