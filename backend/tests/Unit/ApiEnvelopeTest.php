<?php

namespace Tests\Unit;

use App\Support\ApiEnvelope;
use PHPUnit\Framework\TestCase;

final class ApiEnvelopeTest extends TestCase
{
    public function test_ok_preserves_legacy_json_envelope_shape(): void
    {
        $body = ApiEnvelope::ok(['id' => 42], 'Operação concluída.');

        $this->assertSame(['status', 'mensagem', 'data'], array_keys($body));
        $this->assertSame('ok', $body['status']);
        $this->assertSame('Operação concluída.', $body['mensagem']);
        $this->assertSame(['id' => 42], $body['data']);
    }
}
