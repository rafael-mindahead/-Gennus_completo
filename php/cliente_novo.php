<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

$stmt = $conexao->prepare('INSERT INTO clientes (nome,  email, telefone, documento) VALUES(?,?,?,?)');
// 'sssss' = 5 strings
$stmt->bind_param('ssss', $_POST['nome'],$_POST['email'], $_POST['telefone'], $_POST['documento']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Cliente cadastrado com sucesso.';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Falha ao cadastrar cliente.';
}

$stmt->close();
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>