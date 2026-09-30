<?php
require_once __DIR__ . '/../../app/bootstrap.php';
function obterDadosRequisicao(): array
{
    return request_data();
}
