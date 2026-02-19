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

class DebugVeiculoTest extends TestCase
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

    public function test_first(): void
    {
        $clienteUuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        
        $veiculoMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn(null);
        $veiculoMock->shouldReceive('criar')->andReturn([
            'uuid' => 'x', 'marca' => 'Honda', 'modelo' => 'Civic',
            'placa' => 'ABC1234', 'ano' => $this->anoValido, 'cliente_id' => 1,
            'criado_em' => '2024-01-01 10:00:00', 'atualizado_em' => '2024-01-01 10:00:00',
        ]);
        
        $clienteMock = Mockery::mock(ClienteRepositorio::class);
        $clienteMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn(
            new ClienteEntidade(
                uuid: $clienteUuid, nome: 'Dono', documento: '12345678901',
                email: 'dono@email.com', fone: '11999999999',
                criadoEm: new DateTimeImmutable('2024-01-01'),
                atualizadoEm: new DateTimeImmutable('2024-01-01'),
            )
        );
        $clienteMock->shouldReceive('obterIdNumerico')->andReturn(1);
        
        $this->app->instance(VeiculoRepositorio::class, $veiculoMock);
        $this->app->instance(ClienteRepositorio::class, $clienteMock);
        
        $response = $this->postJson('/api/veiculo', [
            'marca' => 'Honda', 'modelo' => 'Civic', 'placa' => 'ABC1234',
            'ano' => $this->anoValido, 'cliente_uuid' => $clienteUuid,
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        echo "\nFirst test: " . $response->getStatusCode() . "\n";
    }

    public function test_second(): void
    {
        $veiculoMock = Mockery::mock(VeiculoRepositorio::class);
        $clienteMock = Mockery::mock(ClienteRepositorio::class);
        
        $this->app->instance(VeiculoRepositorio::class, $veiculoMock);
        $this->app->instance(ClienteRepositorio::class, $clienteMock);
        
        $response = $this->postJson('/api/veiculo', ['marca' => 'Honda']);
        
        echo "\nSecond test status: " . $response->getStatusCode() . "\n";
        echo "Second test body: " . $response->getContent() . "\n";
        
        $this->assertEquals(400, $response->getStatusCode());
    }
}
