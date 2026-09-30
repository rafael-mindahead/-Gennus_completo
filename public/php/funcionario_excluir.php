<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('DELETE FROM funcionarios WHERE id=?');
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $retorno['status'] = 'ok';
        $retorno['mensagem'] = 'Funcionario excluido com sucesso.';
    } else {
        $retorno['status'] = 'nok';
        $retorno['mensagem'] = 'Nenhum funcionario excluido.';
    }
    $stmt->close();
}

$conexao->close();
header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>