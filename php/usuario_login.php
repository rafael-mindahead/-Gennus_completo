<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

$stmt = $conexao->prepare('SELECT * FROM usuarios WHERE email_corporativo=? AND senha_hash=?');
$stmt->bind_param('ss', $_POST['email'], $_POST['senha']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    session_start();
    $_SESSION['usuario'] = $usuario;
    $retorno['status'] = 'ok';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'E-mail ou senha incorretos.';
}

$stmt->close();
$conexao->close();
header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);