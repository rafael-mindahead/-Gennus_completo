<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

$stmt = $conexao->prepare('INSERT INTO produtos (nome,categoria, unidade, custo, preco, estoque) VALUES(?,?,?,?,?,?)');

// 'ssssddd' = 4 strings (nome, nome_mae, categoria, unidade) e 3 decimais/doubles (custo, preco, estoque)
$stmt->bind_param('sssddd', 
    $_POST['nome'], 
    $_POST['categoria'], 
    $_POST['unidade'], 
    $_POST['custo'], 
    $_POST['preco'], 
    $_POST['estoque']
);

$stmt->execute();

if ($stmt->affected_rows > 0) {
    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Produto cadastrado com sucesso.';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Falha ao cadastrar produto.';
}

$stmt->close();
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>