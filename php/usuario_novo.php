<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

// Nota: Em um sistema real, use password_hash($_POST['senha'], PASSWORD_DEFAULT);
$stmt = $conexao->prepare('INSERT INTO usuarios (nome, sobrenome, email_corporativo, telefone, cpf_cnpj, senha_hash) VALUES(?,?,?,?,?,?)');
$stmt->bind_param('ssssss', 
    $_POST['nome'], 
    $_POST['sobrenome'], 
    $_POST['email_corporativo'], 
    $_POST['telefone'], 
    $_POST['cpf_cnpj'], 
    $_POST['senha'] 
);

if ($stmt->execute()) {
    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Conta criada com sucesso!';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Erro: ' . $stmt->error;
}

$stmt->close();
$conexao->close();
header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);