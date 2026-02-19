<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Service;

use App\Infrastructure\Service\JsonWebToken;
use App\Infrastructure\Dto\JsonWebTokenFragment;
use RuntimeException;
use Tests\TestCase;

class JsonWebTokenTest extends TestCase
{
    private JsonWebToken $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        putenv('JWT_SECRET=test-secret-key-for-testing-12345');
        $_ENV['JWT_SECRET'] = 'test-secret-key-for-testing-12345';
        $_SERVER['JWT_SECRET'] = 'test-secret-key-for-testing-12345';
        $this->jwtService = new JsonWebToken();
    }

    protected function tearDown(): void
    {
        putenv('JWT_SECRET');
        unset($_ENV['JWT_SECRET'], $_SERVER['JWT_SECRET']);
        parent::tearDown();
    }

    public function test_construtor_sem_jwt_secret_lanca_excecao(): void
    {
        putenv('JWT_SECRET');
        unset($_ENV['JWT_SECRET'], $_SERVER['JWT_SECRET']);

        // Limpar cache do env
        \Illuminate\Support\Env::getRepository()->clear('JWT_SECRET');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET não configurado');
        new JsonWebToken();
    }

    public function test_generate_retorna_string_jwt(): void
    {
        $token = $this->jwtService->generate(['sub' => 'user-123']);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function test_validate_retorna_fragment_para_token_valido(): void
    {
        $token = $this->jwtService->generate(['sub' => 'user-456']);
        $result = $this->jwtService->validate($token);

        $this->assertInstanceOf(JsonWebTokenFragment::class, $result);
        $this->assertEquals('user-456', $result->sub);
        $this->assertEquals('http://localhost', $result->iss);
        $this->assertEquals('http://localhost', $result->aud);
    }

    public function test_validate_retorna_null_para_token_invalido(): void
    {
        $result = $this->jwtService->validate('token.invalido.aqui');

        $this->assertNull($result);
    }

    public function test_refresh_gera_novo_token(): void
    {
        $originalToken = $this->jwtService->generate(['sub' => 'user-789']);
        $refreshedToken = $this->jwtService->refresh($originalToken);

        $this->assertIsString($refreshedToken);
        $this->assertNotEmpty($refreshedToken);

        $decoded = $this->jwtService->validate($refreshedToken);
        $this->assertEquals('user-789', $decoded->sub);
    }

    public function test_refresh_token_invalido_lanca_excecao(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token inválido');

        $this->jwtService->refresh('token.invalido.aqui');
    }

    public function test_invalidate_nao_lanca_excecao(): void
    {
        $token = $this->jwtService->generate(['sub' => 'user-000']);

        $this->jwtService->invalidate($token);
        $this->assertTrue(true);
    }
}
