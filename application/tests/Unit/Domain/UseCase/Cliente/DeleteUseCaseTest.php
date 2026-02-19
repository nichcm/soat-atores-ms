<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Cliente;

use App\Domain\Entity\Cliente\Entidade;
use App\Domain\UseCase\Cliente\DeleteUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\ClienteGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteUseCaseTest extends TestCase
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
            uuid: 'uuid-para-deletar',
            nome: 'Cliente Deletar',
            documento: '11122233344',
            email: 'deletar@email.com',
            fone: '11977776666',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_deleta_cliente_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarEntidadeFake());

        $this->gatewayMock
            ->expects($this->once())
            ->method('deletar')
            ->with('uuid-para-deletar')
            ->willReturn(true);

        $useCase = new DeleteUseCase($this->gatewayMock);
        $resultado = $useCase->exec('uuid-para-deletar');

        $this->assertTrue($resultado);
    }

    public function test_lanca_excecao_quando_cliente_nao_encontrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->gatewayMock
            ->expects($this->never())
            ->method('deletar');

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Não encontrado com o identificador informado');
        $this->expectExceptionCode(400);

        $useCase = new DeleteUseCase($this->gatewayMock);
        $useCase->exec('uuid-inexistente');
    }
}
