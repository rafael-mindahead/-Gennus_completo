<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('UPDATE produtos SET nome=?,categoria=?, unidade=?, custo=?, preco=?, estoque=? WHERE id=?');
    
    // 'ssssdddi' = 4 strings, 3 doubles, e 1 inteiro (ID) no final
    $stmt->bind_param('sssdddi', 
        $_POST['nome'],  
        $_POST['categoria'], 
        $_POST['unidade'], 
        $_POST['custo'], 
        $_POST['preco'], 
        $_POST['estoque'],
        $_GET['id']
    );
    
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $retorno['status'] = 'ok';
        $retorno['mensagem'] = 'Produto atualizado com sucesso.';
    } else {
        $retorno['status'] = 'nok';
        $retorno['mensagem'] = 'Erro ao atualizar produto.';
    }
    $stmt->close();
}

$conexao->close();
header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>