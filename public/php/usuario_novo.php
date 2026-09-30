<?php
require_once __DIR__ . '/../../app/auth.php';
method('POST');
$data = request_data();
$nome = text_field($data, 'nome', 100);
$sobrenome = text_field($data, 'sobrenome', 100);
$email = text_field($data, 'email_corporativo', 190);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new ApiError('E-mail inválido.');
}
$phone = text_field($data, 'telefone', 30, false);
$document = text_field($data, 'cpf_cnpj', 30, false);
$hash = password_hash(password_input($data, true), PASSWORD_DEFAULT);
query('INSERT INTO usuarios (nome, sobrenome, email_corporativo, telefone, cpf_cnpj, senha_hash) VALUES (?, ?, ?, ?, ?, ?)', [$nome, $sobrenome, $email, $phone ?: null, $document ?: null, $hash]);
respond('ok', 'Conta criada com sucesso!');
