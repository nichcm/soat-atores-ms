<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entity\Cliente\Entidade as ClienteEntidade;
use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepositorio;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class ClienteApiTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function criarEntidade(string $uuid = 'uuid-test-123'): ClienteEntidade
    {
        return new ClienteEntidade(
            uuid: $uuid,
            nome: 'João Silva',
            documento: '12345678901',
            email: 'joao@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    private function dadosCriacao(): array
    {
        return [
            'uuid'          => 'uuid-test-123',
            'nome'          => 'João Silva',
            'documento'     => '12345678901',
            'email'         => 'joao@email.com',
            'fone'          => '11999999999',
            'criado_em'     => '2024-01-01 10:00:00',
            'atualizado_em' => '2024-01-01 10:00:00',
        ];
    }

    public function test_create_cliente_com_sucesso(): void
    {
        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn(null);
        $repositorioMock->shouldReceive('criar')->andReturn($this->dadosCriacao());

        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->postJson('/api/cliente', [
            'nome'      => 'João Silva',
            'documento' => '12345678901',
            'email'     => 'joao@email.com',
            'fone'      => '11999999999',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['nome' => 'João Silva']);
    }

    public function test_create_cliente_com_dados_invalidos_retorna_400(): void
    {
        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->postJson('/api/cliente', [
            'nome' => 'João Silva',
            // Faltando campos obrigatórios
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }

    public function test_read_lista_clientes(): void
    {
        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $repositorioMock->shouldReceive('listar')->andReturn([
            [
                'uuid'          => 'uuid-1',
                'nome'          => 'Cliente 1',
                'documento'     => '12345678901',
                'email'         => 'c1@email.com',
                'fone'          => '11999999999',
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ],
        ]);

        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->getJson('/api/cliente');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_read_one_cliente_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $entidade = $this->criarEntidade($uuid);

        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($entidade);

        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->getJson('/api/cliente/' . $uuid);

        $response->assertStatus(200);
        $response->assertJsonFragment(['uuid' => $uuid]);
    }

    public function test_read_one_com_uuid_invalido_retorna_400(): void
    {
        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->getJson('/api/cliente/nao-e-um-uuid');

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }

    public function test_update_cliente_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $entidade = $this->criarEntidade($uuid);

        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($entidade);
        $repositorioMock->shouldReceive('atualizar')->andReturn([
            'uuid'          => $uuid,
            'nome'          => 'João Atualizado',
            'documento'     => '12345678901',
            'email'         => 'joao@email.com',
            'fone'          => '11999999999',
            'criado_em'     => '2024-01-01 10:00:00',
            'atualizado_em' => '2024-06-01 12:00:00',
            'deletado_em'   => null,
        ]);

        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->putJson('/api/cliente/' . $uuid, [
            'nome' => 'João Atualizado',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['nome' => 'João Atualizado']);
    }

    public function test_delete_cliente_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($this->criarEntidade($uuid));
        $repositorioMock->shouldReceive('deletar')->with($uuid)->andReturn(true);

        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->deleteJson('/api/cliente/' . $uuid);

        $response->assertStatus(204);
    }

    public function test_delete_com_uuid_invalido_retorna_400(): void
    {
        $repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $this->app->instance(ClienteRepositorio::class, $repositorioMock);

        $response = $this->deleteJson('/api/cliente/nao-e-um-uuid');

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }
}
