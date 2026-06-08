<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

$stmt = $conexao->prepare('INSERT INTO funcionarios (nome, tipo_contrato, documento, salario_base, status, vt, vr, extras) VALUES(?,?,?,?,?,?,?,?)');

// 'ssssdssss' = string, string, string, string, double, string, string, string, string
$stmt->bind_param('sssdssss', 
    $_POST['nome'],  
    $_POST['tipo'], 
    $_POST['doc'], 
    $_POST['salario'], 
    $_POST['status'], 
    $_POST['vt'], 
    $_POST['vr'], 
    $_POST['extras']
);

$stmt->execute();

if ($stmt->affected_rows > 0) {
    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Funcionario cadastrado com sucesso.';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Falha ao cadastrar funcionario.';
}

$stmt->close();
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>