<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entity\Cliente\Entidade as ClienteEntidade;
use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepositorio;
use App\Domain\Entity\Veiculo\Entidade as VeiculoEntidade;
use App\Domain\Entity\Veiculo\RepositorioInterface as VeiculoRepositorio;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class VeiculoApiTest extends TestCase
{
    private int $anoValido;

    public function setUp(): void
    {
        parent::setUp();
        $this->anoValido = (int) date('Y');
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function criarVeiculoEntidade(string $uuid = 'veiculo-uuid-test'): VeiculoEntidade
    {
        return new VeiculoEntidade(
            uuid: $uuid,
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'ABC1234',
            ano: $this->anoValido,
            clienteId: 1,
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    private function criarClienteEntidade(string $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'): ClienteEntidade
    {
        return new ClienteEntidade(
            uuid: $uuid,
            nome: 'Dono do Veículo',
            documento: '12345678901',
            email: 'dono@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    private function dadosCriacao(string $uuid = 'veiculo-uuid-test'): array
    {
        return [
            'uuid'          => $uuid,
            'marca'         => 'Honda',
            'modelo'        => 'Civic',
            'placa'         => 'ABC1234',
            'ano'           => $this->anoValido,
            'cliente_id'    => 1,
            'criado_em'     => '2024-01-01 10:00:00',
            'atualizado_em' => '2024-01-01 10:00:00',
        ];
    }

    public function test_create_veiculo_com_sucesso(): void
    {
        $clienteUuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn(null);
        $veiculoRepositorioMock->shouldReceive('criar')->andReturn($this->dadosCriacao());

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);
        $clienteRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($clienteUuid, 'uuid')
            ->andReturn($this->criarClienteEntidade($clienteUuid));
        $clienteRepositorioMock->shouldReceive('obterIdNumerico')
            ->with($clienteUuid)
            ->andReturn(1);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->postJson('/api/veiculo', [
            'marca'        => 'Honda',
            'modelo'       => 'Civic',
            'placa'        => 'ABC1234',
            'ano'          => $this->anoValido,
            'cliente_uuid' => $clienteUuid,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['placa' => 'ABC1234']);
    }

    public function test_create_veiculo_com_dados_invalidos_retorna_400(): void
    {
        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->postJson('/api/veiculo', [
            'marca' => 'Honda',
            // Faltando campos obrigatórios
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }

    public function test_read_lista_veiculos(): void
    {
        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('listar')->andReturn([
            [
                'uuid'          => 'uuid-1',
                'marca'         => 'Honda',
                'modelo'        => 'Civic',
                'placa'         => 'ABC1234',
                'ano'           => $this->anoValido,
                'cliente_id'    => 1,
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ],
        ]);

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->getJson('/api/veiculo');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_read_one_veiculo_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $entidade = $this->criarVeiculoEntidade($uuid);

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($entidade);

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->getJson('/api/veiculo/' . $uuid);

        $response->assertStatus(200);
        $response->assertJsonFragment(['uuid' => $uuid]);
    }

    public function test_read_one_com_uuid_invalido_retorna_400(): void
    {
        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->getJson('/api/veiculo/nao-e-um-uuid');

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }

    public function test_update_veiculo_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $entidade = $this->criarVeiculoEntidade($uuid);

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($entidade);
        $veiculoRepositorioMock->shouldReceive('atualizar')->andReturn(array_merge(
            $this->dadosCriacao($uuid),
            ['modelo' => 'Accord', 'deletado_em' => null]
        ));

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->putJson('/api/veiculo/' . $uuid, [
            'modelo' => 'Accord',
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_veiculo_com_sucesso(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($this->criarVeiculoEntidade($uuid));
        $veiculoRepositorioMock->shouldReceive('deletar')->with($uuid)->andReturn(true);

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->deleteJson('/api/veiculo/' . $uuid);

        $response->assertStatus(204);
    }

    public function test_delete_com_uuid_invalido_retorna_400(): void
    {
        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $this->app->instance(VeiculoRepositorio::class, $veiculoRepositorioMock);
        $this->app->instance(ClienteRepositorio::class, $clienteRepositorioMock);

        $response = $this->deleteJson('/api/veiculo/nao-e-um-uuid');

        $response->assertStatus(400);
        $response->assertJsonFragment(['err' => true]);
    }
}
