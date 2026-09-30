<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

if (isset($_GET['id'])) {
    $stmt = $conexao->prepare('UPDATE funcionarios SET nome=?, tipo_contrato=?, documento=?, salario_base=?, status=?, vt=?, vr=?, extras=? WHERE id=?');
    
    // 'ssssdssssi' = adiciona um Inteiro no final para o ID
    $stmt->bind_param('sssdssssi', 
        $_POST['nome'], 
        $_POST['tipo'], 
        $_POST['doc'], 
        $_POST['salario'], 
        $_POST['status'], 
        $_POST['vt'], 
        $_POST['vr'], 
        $_POST['extras'],
        $_GET['id']
    );
    
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $retorno['status'] = 'ok';
        $retorno['mensagem'] = 'Funcionario atualizado com sucesso.';
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