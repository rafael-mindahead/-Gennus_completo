<?php
require_once __DIR__ . '/../../app/bootstrap.php';
method('POST');
start_session();
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
respond('ok', 'Logoff efetuado.');
