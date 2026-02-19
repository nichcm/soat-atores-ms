<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entity\Usuario\RepositorioInterface as UsuarioRepositorio;
use Mockery;
use Tests\TestCase;

class UsuarioApiTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function dadosCriacao(string $uuid = 'uuid-usuario-123'): array
    {
        return [
            'uuid'          => $uuid,
            'nome'          => 'João Silva',
            'email'         => 'joao@email.com',
            'senha'         => password_hash('senha123', PASSWORD_BCRYPT),
            'ativo'         => true,
            'perfil'        => 'atendente',
            'criado_em'     => '2024-01-01 10:00:00',
            'atualizado_em' => '2024-01-01 10:00:00',
            'deletado_em'   => null,
        ];
    }

    public function test_create_usuario_com_sucesso(): void
    {
        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn(null);
        $repositorioMock->shouldReceive('criar')->andReturn(['uuid' => 'uuid-usuario-123']);

        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->postJson('/api/usuario', [
            'nome'   => 'João Silva',
            'email'  => 'joao@email.com',
            'senha'  => 'senha123',
            'perfil' => 'atendente',
        ]);

        $response->assertStatus(201);
    }

    public function test_create_usuario_com_dados_invalidos_retorna_400(): void
    {
        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->postJson('/api/usuario', [
            'nome' => 'João Silva',
            // Faltando campos obrigatórios
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }

    public function test_read_lista_usuarios(): void
    {
        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('listar')->andReturn([
            [
                'uuid'          => 'uuid-1',
                'nome'          => 'Usuario 1',
                'email'         => 'u1@email.com',
                'ativo'         => true,
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ],
        ]);

        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->getJson('/api/usuario');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_update_usuario_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn(true);
        $repositorioMock->shouldReceive('atualizar')->andReturn(array_merge(
            $this->dadosCriacao($uuid),
            ['nome' => 'João Atualizado']
        ));

        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->putJson('/api/usuario/' . $uuid, [
            'nome' => 'João Atualizado',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_com_uuid_invalido_retorna_400(): void
    {
        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->putJson('/api/usuario/nao-e-um-uuid', [
            'nome' => 'João Atualizado',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }

    public function test_delete_usuario_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn(true);
        $repositorioMock->shouldReceive('deletar')->with($uuid)->andReturn(true);

        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->deleteJson('/api/usuario/' . $uuid);

        $response->assertStatus(204);
    }

    public function test_delete_com_uuid_invalido_retorna_400(): void
    {
        $repositorioMock = Mockery::mock(UsuarioRepositorio::class);
        $this->app->instance(UsuarioRepositorio::class, $repositorioMock);

        $response = $this->deleteJson('/api/usuario/nao-e-um-uuid');

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }
}
