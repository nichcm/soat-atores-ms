<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Veiculo;

use App\Domain\Entity\Veiculo\Entidade;
use App\Domain\UseCase\Veiculo\DeleteUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\VeiculoGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;
    private int $anoValido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(VeiculoGateway::class);
        $this->anoValido = (int) date('Y');
    }

    private function criarVeiculoFake(): Entidade
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

    public function test_deleta_veiculo_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarVeiculoFake());

        $this->gatewayMock
            ->method('deletar')
            ->willReturn(true);

        $useCase = new DeleteUseCase($this->gatewayMock);
        $resultado = $useCase->exec('uuid-veiculo-123');

        $this->assertTrue($resultado);
    }

    public function test_lanca_excecao_quando_veiculo_nao_encontrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Não encontrado com o identificador informado');
        $this->expectExceptionCode(400);

        $useCase = new DeleteUseCase($this->gatewayMock);
        $useCase->exec('uuid-inexistente');
    }

    public function test_deletar_retorna_false_quando_repositorio_falha(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarVeiculoFake());

        $this->gatewayMock
            ->method('deletar')
            ->willReturn(false);

        $useCase = new DeleteUseCase($this->gatewayMock);
        $resultado = $useCase->exec('uuid-veiculo-123');

        $this->assertFalse($resultado);
    }
}
