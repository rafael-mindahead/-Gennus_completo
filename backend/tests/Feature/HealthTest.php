<?php
namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_api_health_returns_expected_contract(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson([
            'status' => 'ok',
            'mensagem' => 'Gennus 2.0 API em desenvolvimento.',
            'data' => ['service' => 'gennus-api', 'version' => '2.0-dev'],
        ]);
    }
}
