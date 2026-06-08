<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('UPDATE despesas SET descricao=?, valor=?,WHERE id=?');
    // 'sdsi' = String, Double, String, Inteiro
    $stmt->bind_param('sdi', $_POST['descricao'], $_POST['valor'], $_GET['id']);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $retorno['status'] = 'ok';
        $retorno['mensagem'] = 'Despesa atualizada com sucesso.';
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