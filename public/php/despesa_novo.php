<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

$stmt = $conexao->prepare('INSERT INTO despesas (descricao, valor) VALUES(?,?)');
// 'sds' significa String, Double (decimal), String
$stmt->bind_param('sd', $_POST['descricao'], $_POST['valor']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Despesa cadastrada com sucesso.';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Falha ao cadastrar despesa.';
}

$stmt->close();
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>