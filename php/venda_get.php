<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('SELECT * FROM vendas WHERE id = ?');
    $stmt->bind_param('i', $_GET['id']);
} else {
    $stmt = $conexao->prepare('SELECT * FROM vendas ORDER BY id DESC');
}

$stmt->execute();
$resultado = $stmt->get_result();
$tabela = [];

if ($resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {

    $linha['id'] = (int) $linha['id'];
    $linha['produto_id'] = (int) $linha['produto_id'];

    $linha['qtd'] = (float) $linha['qtd'];
    $linha['valor_total'] = (float) $linha['valor_total'];
    $linha['custo_total'] = (float) $linha['custo_total'];

    $tabela[] = $linha;
    }
    $retorno['status'] = 'ok';
    $retorno['data'] = $tabela;
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Nenhuma venda registrada.';
}

$stmt->close();
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>