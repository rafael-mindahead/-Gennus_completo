<?php
require_once __DIR__ . '/../../app/auth.php';
method('POST');
$user = require_user();
if (!empty($user['legacy'])) {
    throw new ApiError('Use o cadastro principal para editar o perfil.', 403);
}
$data = request_data();
$nome = text_field($data, 'nome', 100);
$sobrenome = text_field($data, 'sobrenome', 100, false);
if (($data['senha'] ?? '') !== '') {
    $hash = password_hash(password_input($data, true), PASSWORD_DEFAULT);
    query('UPDATE usuarios SET nome = ?, sobrenome = ?, senha_hash = ? WHERE id = ?', [$nome, $sobrenome, $hash, $user['id']]);
} else {
    query('UPDATE usuarios SET nome = ?, sobrenome = ? WHERE id = ?', [$nome, $sobrenome, $user['id']]);
}
$user = query('SELECT * FROM usuarios WHERE id = ?', [$user['id']])->get_result()->fetch_assoc();
if (!$user) {
    throw new ApiError('Usuário não encontrado.', 404);
}
start_session();
$_SESSION['usuario'] = safe_user($user);
respond('ok', 'Perfil atualizado.', $_SESSION['usuario']);
