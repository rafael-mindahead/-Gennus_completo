<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('UPDATE vendas SET qtd=?, valor_total=?, custo_total=? WHERE id=?');
    // 'dddi' = 3 decimais e 1 inteiro (ID)
    $stmt->bind_param('dddi', $_POST['qtd'], $_POST['valor_total'], $_POST['custo_total'], $_GET['id']);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $retorno['status'] = 'ok';
        $retorno['mensagem'] = 'Venda atualizada.';
    } else {
        $retorno['status'] = 'nok';
        $retorno['mensagem'] = 'Erro ao atualizar.';
    }
    $stmt->close();
}
$conexao->close();
header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>