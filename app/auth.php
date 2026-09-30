<?php
require_once __DIR__ . '/bootstrap.php';

function password_input(array $data, bool $new = false): string
{
    $password = $data['senha'] ?? '';
    if (!is_string($password) || strlen($password) > 72 || strlen($password) < ($new ? 8 : 1)) {
        throw new ApiError($new ? 'A senha deve ter entre 8 e 72 bytes.' : 'Senha inválida.');
    }
    return $password;
}

function safe_user(array $user): array
{
    unset($user['senha_hash'], $user['senha']);
    $user['id'] = (int) $user['id'];
    return $user;
}

function login(bool $legacy = false): never
{
    method('POST');
    $data = request_data();
    $password = password_input($data);
    $login = text_field($data, $legacy ? 'usuario' : 'email', 190);
    $table = $legacy ? 'cliente' : 'usuarios';
    $column = $legacy ? 'usuario' : 'email_corporativo';
    $passwordColumn = $legacy ? 'senha' : 'senha_hash';
    $user = query("SELECT * FROM $table WHERE $column = ?", [$login])->get_result()->fetch_assoc();
    $stored = $user[$passwordColumn] ?? '';
    $isHash = password_get_info($stored)['algo'] !== null;
    $valid = $user && ($isHash ? password_verify($password, $stored) : hash_equals($stored, $password));
    if (!$valid) {
        throw new ApiError('E-mail ou senha incorretos.', 401);
    }
    if (!$isHash || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        query("UPDATE $table SET $passwordColumn = ? WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    start_session();
    session_regenerate_id(true);
    $user = safe_user($user);
    if ($legacy) {
        $user += ['nome' => $login, 'sobrenome' => '', 'email_corporativo' => '', 'legacy' => true];
    }
    $_SESSION['usuario'] = $user;
    respond('ok', '', $user);
}
