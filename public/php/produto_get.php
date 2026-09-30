<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->bind_param('i', $_GET['id']);
} else {
    $stmt = $conexao->prepare('SELECT * FROM produtos');
}

$stmt->execute();
$resultado = $stmt->get_result();
$tabela = [];

if ($resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {

    $linha['id'] = (int) $linha['id'];
    $linha['custo'] = (float) $linha['custo'];
    $linha['preco'] = (float) $linha['preco'];
    $linha['estoque'] = (float) $linha['estoque'];

    $tabela[] = $linha;
    }
    $retorno['status'] = 'ok';
    $retorno['data'] = $tabela;
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Nenhum produto cadastrado.';
}

$stmt->close();
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>