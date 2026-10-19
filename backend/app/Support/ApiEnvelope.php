<?php

namespace App\Support;

final class ApiEnvelope
{
    /** @param array<string, mixed> $data
     *  @return array{status: string, mensagem: string, data: array<string, mixed>}
     */
    public static function ok(array $data = [], string $message = ''): array
    {
        return ['status' => 'ok', 'mensagem' => $message, 'data' => $data];
    }
}
