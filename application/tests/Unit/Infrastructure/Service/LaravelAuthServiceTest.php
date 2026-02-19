<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Service;

use App\Domain\Entity\Usuario\Entidade as UsuarioEntidade;
use App\Domain\Entity\Usuario\RepositorioInterface as UsuarioRepositorio;
use App\Infrastructure\Service\LaravelAuthService;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class LaravelAuthServiceTest extends TestCase
{
    private UsuarioRepositorio $repositorioMock;
    private LaravelAuthService $service;
    private string $senhaHash;

    public function setUp(): void
    {
        parent::setUp();
        $this->senhaHash = password_hash('senha123', PASSWORD_BCRYPT);
        $this->repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $this->service = new LaravelAuthService($this->repositorioMock);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function criarEntidade(): UsuarioEntidade
    {
        return new UsuarioEntidade(
            uuid: 'uuid-123',
            nome: 'Test User',
            email: 'test@email.com',
            senha: $this->senhaHash,
            ativo: true,
            perfil: 'atendente',
            criadoEm: new DateTimeImmutable('2024-01-01'),
            atualizadoEm: new DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_attempt_retorna_entidade_com_credenciais_validas(): void
    {
        $this->repositorioMock
            ->shouldReceive('encontrarPorIdentificadorUnico')
            ->with('test@email.com', 'email')
            ->andReturn($this->criarEntidade());

        $resultado = $this->service->attempt('test@email.com', 'senha123');

        $this->assertInstanceOf(UsuarioEntidade::class, $resultado);
        $this->assertEquals('test@email.com', $resultado->email);
    }

    public function test_attempt_retorna_null_quando_usuario_nao_encontrado(): void
    {
        $this->repositorioMock
            ->shouldReceive('encontrarPorIdentificadorUnico')
            ->with('naoexiste@email.com', 'email')
            ->andReturn(null);

        $resultado = $this->service->attempt('naoexiste@email.com', 'senha123');

        $this->assertNull($resultado);
    }

    public function test_attempt_retorna_null_quando_senha_incorreta(): void
    {
        $this->repositorioMock
            ->shouldReceive('encontrarPorIdentificadorUnico')
            ->with('test@email.com', 'email')
            ->andReturn($this->criarEntidade());

        $resultado = $this->service->attempt('test@email.com', 'senha-errada');

        $this->assertNull($resultado);
    }

    public function test_check_retorna_false_sem_usuario_na_request(): void
    {
        $resultado = $this->service->check();

        $this->assertFalse($resultado);
    }

    public function test_check_retorna_true_com_usuario_na_request(): void
    {
        request()->attributes->set('user', $this->criarEntidade());

        $resultado = $this->service->check();

        $this->assertTrue($resultado);
    }

    public function test_user_retorna_null_sem_usuario_na_request(): void
    {
        $resultado = $this->service->user();

        $this->assertNull($resultado);
    }

    public function test_user_retorna_entidade_com_usuario_na_request(): void
    {
        $entidade = $this->criarEntidade();
        request()->attributes->set('user', $entidade);

        $resultado = $this->service->user();

        $this->assertInstanceOf(UsuarioEntidade::class, $resultado);
        $this->assertEquals('test@email.com', $resultado->email);
    }
}
