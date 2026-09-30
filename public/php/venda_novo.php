<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

// 1. Prepara a inserção da venda
$stmt = $conexao->prepare('INSERT INTO vendas (produto_id, produto_nome, qtd, unidade, valor_total, custo_total) VALUES(?,?,?,?,?,?)');
// 'isdsdd' = Int, String, Decimal, String, Decimal, Decimal
$stmt->bind_param('isdsdd', 
    $_POST['produto_id'], 
    $_POST['produto_nome'], 
    $_POST['qtd'], 
    $_POST['unidade'], 
    $_POST['valor_total'], 
    $_POST['custo_total']
);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    
    // 2. Atualiza o estoque do produto (diminui a quantidade vendida)
    $stmt_estoque = $conexao->prepare('UPDATE produtos SET estoque = estoque - ? WHERE id = ?');
    $stmt_estoque->bind_param('di', $_POST['qtd'], $_POST['produto_id']);
    $stmt_estoque->execute();
    $stmt_estoque->close();

    $retorno['status'] = 'ok';
    $retorno['mensagem'] = 'Venda registrada com sucesso.';
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Falha ao registrar venda.';
    $stmt->close();
}

$conexao->close();
header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>