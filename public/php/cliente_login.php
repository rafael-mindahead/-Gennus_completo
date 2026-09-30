<?php
include_once('conexao.php');
$retorno = ['status'=>'', 'mensagem'=>'', 'data'=>[]];

$stmt = $conexao->prepare('SELECT * FROM cliente WHERE usuario=? AND senha=?');
$stmt->bind_param('ss', $_POST['usuario'], $_POST['senha']);
$stmt->execute();
$resultado = $stmt->get_result();

$tabela = [];
if ($resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {
        $tabela[] = $linha;
    }
    session_start();
    $_SESSION['usuario'] = $tabela;
    $retorno['status'] = 'ok';
    $retorno['data'] = $tabela;
} else {
    $retorno['status'] = 'nok';
    $retorno['mensagem'] = 'Credenciais invalidas.';
}

$stmt->close(); 
$conexao->close();

header('Content-type: application/json; charset=utf-8');
echo json_encode($retorno);
?>