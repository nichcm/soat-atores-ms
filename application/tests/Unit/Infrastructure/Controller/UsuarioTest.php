<?php

namespace Tests\Unit\Infrastructure\Controller;

use Tests\TestCase;
use Mockery;
use App\Infrastructure\Controller\Usuario as UsuarioController;
use App\Domain\Entity\Usuario\RepositorioInterface as UsuarioRepositorio;
use App\Domain\Entity\Usuario\Entidade as UsuarioEntidade;
use App\Infrastructure\Dto\UsuarioDto;
use App\Infrastructure\Dto\AuthenticatedDto;
use App\Infrastructure\Gateway\UsuarioGateway;
use App\Domain\UseCase\Usuario\AuthenticateUseCase;

class UsuarioTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCriarUsuarioComSucesso()
    {
        $dados = new UsuarioDto(
            nome: 'Test User',
            email: 'test@example.com',
            senha: 'password',
            perfil: 'atendente'
        );

        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with('test@example.com', 'email')
            ->andReturn(null);
        $repositorioMock->shouldReceive('criar')
            ->andReturn(['uuid' => 'new-uuid']);

        $controller = new UsuarioController();
        $resultado = $controller->criar($dados, $repositorioMock);

        $this->assertIsArray($resultado);
        $this->assertEquals('Test User', $resultado['nome']);
    }

    public function testListarUsuarios()
    {
        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('listar')
            ->andReturn([
                ['uuid' => 'uuid-1', 'nome' => 'Usuario 1', 'email' => 'u1@test.com', 'ativo' => true, 'criado_em' => '2024-01-01 10:00:00', 'atualizado_em' => '2024-01-01 10:00:00'],
            ]);

        $controller = new UsuarioController();
        $resultado = $controller->listar($repositorioMock);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    public function testDeletarUsuarioComSucesso()
    {
        $uuid = 'some-uuid';

        $usuarioEntidadeMock = Mockery::mock(UsuarioEntidade::class);

        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($usuarioEntidadeMock);
        $repositorioMock->shouldReceive('deletar')
            ->andReturn(true);

        $controller = new UsuarioController();
        $resultado = $controller->deletar($uuid, $repositorioMock);

        $this->assertTrue($resultado);
    }

    public function testAtualizarUsuarioComSucesso()
    {
        $uuid = 'some-uuid';
        $senhaHash = password_hash('password', PASSWORD_BCRYPT);
        $dados = ['nome' => 'Updated Name'];

        $usuarioEntidadeMock = Mockery::mock(UsuarioEntidade::class);

        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($usuarioEntidadeMock);
        $repositorioMock->shouldReceive('atualizar')
            ->andReturn([
                'uuid'          => $uuid,
                'nome'          => 'Updated Name',
                'email'         => 'test@example.com',
                'senha'         => $senhaHash,
                'ativo'         => true,
                'perfil'        => 'atendente',
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-06-01 12:00:00',
                'deletado_em'   => null,
            ]);

        $controller = new UsuarioController();
        $resultado = $controller->atualizar($uuid, $dados, $repositorioMock);

        $this->assertIsArray($resultado);
        $this->assertEquals('Updated Name', $resultado['nome']);
    }

    public function testAutenticarComSucesso()
    {
        $email = 'test@example.com';
        $senha = 'password';

        $usuarioEntidadeMock = Mockery::mock(UsuarioEntidade::class);
        $authDto = new AuthenticatedDto($usuarioEntidadeMock, 'fake-jwt-token', 'Bearer');

        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $this->app->instance(UsuarioGateway::class, new UsuarioGateway($repositorioMock));

        $authenticateUseCaseMock = Mockery::mock(AuthenticateUseCase::class);
        $authenticateUseCaseMock->shouldReceive('exec')
            ->andReturn($authDto);

        $controller = new UsuarioController();
        $resultado = $controller->authenticate($email, $senha, $authenticateUseCaseMock);

        $this->assertInstanceOf(AuthenticatedDto::class, $resultado);
        $this->assertEquals('fake-jwt-token', $resultado->token);
    }
}
