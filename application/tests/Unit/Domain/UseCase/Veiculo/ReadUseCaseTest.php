<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Veiculo;

use App\Domain\UseCase\Veiculo\ReadUseCase;
use App\Infrastructure\Gateway\ClienteGateway;
use App\Infrastructure\Gateway\VeiculoGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadUseCaseTest extends TestCase
{
    private MockObject $veiculoGatewayMock;
    private MockObject $clienteGatewayMock;
    private int $anoValido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->veiculoGatewayMock = $this->createMock(VeiculoGateway::class);
        $this->clienteGatewayMock = $this->createMock(ClienteGateway::class);
        $this->anoValido = (int) date('Y');
    }

    public function test_retorna_lista_de_veiculos(): void
    {
        $this->veiculoGatewayMock
            ->expects($this->once())
            ->method('listar')
            ->willReturn([
                [
                    'uuid'          => 'uuid-v1',
                    'marca'         => 'Ford',
                    'modelo'        => 'Ka',
                    'placa'         => 'AAA1111',
                    'ano'           => $this->anoValido,
                    'cliente_id'    => 1,
                    'criado_em'     => '2024-01-01 10:00:00',
                    'atualizado_em' => '2024-01-01 10:00:00',
                ],
                [
                    'uuid'          => 'uuid-v2',
                    'marca'         => 'Chevrolet',
                    'modelo'        => 'Onix',
                    'placa'         => 'BBB2222',
                    'ano'           => 2020,
                    'cliente_id'    => 2,
                    'criado_em'     => '2024-02-01 10:00:00',
                    'atualizado_em' => '2024-02-01 10:00:00',
                ],
            ]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertArrayHasKey('uuid', $resultado[0]);
        $this->assertEquals('uuid-v1', $resultado[0]['uuid']);
    }

    public function test_retorna_lista_vazia(): void
    {
        $this->veiculoGatewayMock
            ->method('listar')
            ->willReturn([]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_resultado_tem_formato_http_response(): void
    {
        $this->veiculoGatewayMock
            ->method('listar')
            ->willReturn([
                [
                    'uuid'          => 'uuid-v3',
                    'marca'         => 'Toyota',
                    'modelo'        => 'Corolla',
                    'placa'         => 'CCC3333',
                    'ano'           => 2022,
                    'cliente_id'    => 5,
                    'criado_em'     => '2024-03-01 10:00:00',
                    'atualizado_em' => '2024-03-01 10:00:00',
                ],
            ]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertArrayHasKey('marca', $resultado[0]);
        $this->assertArrayHasKey('modelo', $resultado[0]);
        $this->assertArrayHasKey('placa', $resultado[0]);
        $this->assertArrayHasKey('ano', $resultado[0]);
        $this->assertArrayHasKey('criado_em', $resultado[0]);
    }
}
