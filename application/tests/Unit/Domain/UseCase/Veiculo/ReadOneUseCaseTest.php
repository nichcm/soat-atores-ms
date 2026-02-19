<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Veiculo;

use App\Domain\Entity\Veiculo\Entidade;
use App\Domain\UseCase\Veiculo\ReadOneUseCase;
use App\Infrastructure\Gateway\ClienteGateway;
use App\Infrastructure\Gateway\VeiculoGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadOneUseCaseTest extends TestCase
{
    private MockObject $veiculoGatewayMock;
    private MockObject $clienteGatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->veiculoGatewayMock = $this->createMock(VeiculoGateway::class);
        $this->clienteGatewayMock = $this->createMock(ClienteGateway::class);
    }

    private function criarVeiculoFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-veiculo-abc',
            marca: 'Fiat',
            modelo: 'Argo',
            placa: 'DDD4444',
            ano: 2021,
            clienteId: 10,
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_retorna_array_quando_veiculo_encontrado(): void
    {
        $this->veiculoGatewayMock
            ->expects($this->once())
            ->method('encontrarPorIdentificadorUnico')
            ->with('uuid-veiculo-abc', 'uuid')
            ->willReturn($this->criarVeiculoFake());

        $useCase = new ReadOneUseCase('uuid-veiculo-abc');
        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('uuid', $resultado);
        $this->assertEquals('uuid-veiculo-abc', $resultado['uuid']);
    }

    public function test_retorna_null_quando_veiculo_nao_encontrado(): void
    {
        $this->veiculoGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $useCase = new ReadOneUseCase('uuid-inexistente');
        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertNull($resultado);
    }

    public function test_retorna_null_quando_uuid_vazio(): void
    {
        $this->veiculoGatewayMock
            ->expects($this->never())
            ->method('encontrarPorIdentificadorUnico');

        $useCase = new ReadOneUseCase('');
        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertNull($resultado);
    }
}
