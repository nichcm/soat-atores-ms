<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Cliente;

use App\Domain\Entity\Cliente\Entidade;
use App\Domain\UseCase\Cliente\ReadOneUseCase;
use App\Infrastructure\Gateway\ClienteGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadOneUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(ClienteGateway::class);
    }

    private function criarEntidadeFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-cliente-xyz',
            nome: 'Teste Cliente',
            documento: '12345678901',
            email: 'teste@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_retorna_array_quando_cliente_encontrado(): void
    {
        $this->gatewayMock
            ->expects($this->once())
            ->method('encontrarPorIdentificadorUnico')
            ->with('uuid-cliente-xyz', 'uuid')
            ->willReturn($this->criarEntidadeFake());

        $useCase = new ReadOneUseCase('uuid-cliente-xyz');
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('uuid', $resultado);
        $this->assertEquals('uuid-cliente-xyz', $resultado['uuid']);
    }

    public function test_retorna_null_quando_cliente_nao_encontrado(): void
    {
        $this->gatewayMock
            ->expects($this->once())
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $useCase = new ReadOneUseCase('uuid-inexistente');
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertNull($resultado);
    }

    public function test_retorna_null_quando_uuid_vazio(): void
    {
        $this->gatewayMock
            ->expects($this->never())
            ->method('encontrarPorIdentificadorUnico');

        $useCase = new ReadOneUseCase('');
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertNull($resultado);
    }
}
