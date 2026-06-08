<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('UPDATE clientes SET nome=?,  email=?, telefone=?, documento=? WHERE id=?');
    // 'sssssi' = 5 strings e 1 inteiro (o id)
    $stmt->bind_param('ssssi', $_POST['nome'], $_POST['email'], $_POST['telefone'], $_POST['documento'], $_GET['id']);
    $stmt->execute();

    // Se affected_rows >= 0 (pode ser 0 se o usuário clicar em salvar sem mudar nenhum texto)
    if ($stmt->affected_rows >= 0) {
        $retorno['status'] = 'ok';
        $retorno['mensagem'] = 'Cliente atualizado com sucesso.';
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