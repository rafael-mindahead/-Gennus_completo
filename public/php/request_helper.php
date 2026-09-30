<?php

function obterDadosRequisicao(): array
{
    $contentType =
        $_SERVER['CONTENT_TYPE'] ?? '';

    if (
        str_contains(
            $contentType,
            'application/json'
        )
    ) {

        $json = file_get_contents(
            'php://input'
        );

        $dados = json_decode(
            $json,
            true
        );

        return is_array($dados)
            ? $dados
            : [];
    }

    return $_POST;
}