<?php

declare(strict_types=1);

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/database.php';

set_exception_handler(function (Throwable $error): void {
    if ($error instanceof ApiError) {
        respond('nok', $error->getMessage(), [], $error->status);
    }
    if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
        respond('nok', 'E-mail ou documento já cadastrado.', [], 409);
    }
    error_log((string) $error);
    respond('nok', 'Não foi possível concluir a operação.', [], 500);
});
