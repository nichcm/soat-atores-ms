<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Gateway;

use App\Domain\Entity\Veiculo\Entidade;
use App\Domain\Entity\Veiculo\RepositorioInterface;
use App\Infrastructure\Gateway\VeiculoGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VeiculoGatewayTest extends TestCase
{
    private MockObject $repositorioMock;
    private VeiculoGateway $gateway;
    private int $anoValido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositorioMock = $this->createMock(RepositorioInterface::class);
        $this->gateway = new VeiculoGateway($this->repositorioMock);
        $this->anoValido = (int) date('Y');
    }

    private function criarEntidadeFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-veiculo-123',
            marca: 'Toyota',
            modelo: 'Corolla',
            placa: 'AAA1111',
            ano: $this->anoValido,
            clienteId: 1,
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_encontrar_por_identificador_unico_delega_ao_repositorio(): void
    {
        $entidade = $this->criarEntidadeFake();

        $this->repositorioMock
            ->method('encontrarPorIdentificadorUnico')
            ->with('uuid-veiculo-123', 'uuid')
            ->willReturn($entidade);

        $resultado = $this->gateway->encontrarPorIdentificadorUnico('uuid-veiculo-123', 'uuid');

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('uuid-veiculo-123', $resultado->uuid);
    }

    public function test_encontrar_por_identificador_unico_retorna_null_quando_nao_encontrado(): void
    {
        $this->repositorioMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $resultado = $this->gateway->encontrarPorIdentificadorUnico('uuid-inexistente', 'uuid');

        $this->assertNull($resultado);
    }

    public function test_criar_delega_ao_repositorio(): void
    {
        $dados = ['marca' => 'Honda', 'modelo' => 'Civic', 'placa' => 'XYZ9999', 'ano' => $this->anoValido, 'cliente_id' => 1];
        $retorno = array_merge(['uuid' => 'novo-uuid'], $dados);

        $this->repositorioMock
            ->method('criar')
            ->with($dados)
            ->willReturn($retorno);

        $resultado = $this->gateway->criar($dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('novo-uuid', $resultado['uuid']);
    }

    public function test_listar_delega_ao_repositorio(): void
    {
        $this->repositorioMock
            ->method('listar')
            ->with(['*'])
            ->willReturn([['uuid' => 'uuid-1', 'marca' => 'Fiat']]);

        $resultado = $this->gateway->listar();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    public function test_deletar_delega_ao_repositorio(): void
    {
        $this->repositorioMock
            ->method('deletar')
            ->with('uuid-veiculo-123')
            ->willReturn(true);

        $resultado = $this->gateway->deletar('uuid-veiculo-123');

        $this->assertTrue($resultado);
    }

    public function test_atualizar_delega_ao_repositorio(): void
    {
        $dados = ['marca' => 'Honda'];
        $retorno = ['uuid' => 'uuid-veiculo-123', 'marca' => 'Honda'];

        $this->repositorioMock
            ->method('atualizar')
            ->with('uuid-veiculo-123', $dados)
            ->willReturn($retorno);

        $resultado = $this->gateway->atualizar('uuid-veiculo-123', $dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('Honda', $resultado['marca']);
    }
}
