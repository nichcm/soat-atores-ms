<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Veiculo;

use App\Domain\Entity\Veiculo\Entidade;
use App\Domain\UseCase\Veiculo\UpdateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\VeiculoGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateUseCaseTest extends TestCase
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

    public function test_atualiza_veiculo_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarVeiculoFake());

        $this->gatewayMock
            ->method('atualizar')
            ->willReturn([
                'uuid'          => 'uuid-veiculo-123',
                'marca'         => 'Honda',
                'modelo'        => 'Civic',
                'placa'         => 'AAA1111',
                'ano'           => $this->anoValido,
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-06-01 12:00:00',
                'deletado_em'   => null,
            ]);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $resultado = $useCase->exec('uuid-veiculo-123', ['marca' => 'Honda', 'modelo' => 'Civic']);

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('Honda', $resultado->marca);
        $this->assertEquals('Civic', $resultado->modelo);
    }

    public function test_lanca_excecao_quando_uuid_vazio(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('identificador único não informado');
        $this->expectExceptionCode(400);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('', ['marca' => 'Honda']);
    }

    public function test_lanca_excecao_quando_veiculo_nao_encontrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Não encontrado(a)');
        $this->expectExceptionCode(404);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('uuid-inexistente', ['marca' => 'Honda']);
    }

    public function test_lanca_excecao_quando_atualizacao_retorna_vazio(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarVeiculoFake());

        $this->gatewayMock
            ->method('atualizar')
            ->willReturn([]);

        $this->expectException(\TypeError::class);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('uuid-veiculo-123', ['marca' => 'Honda']);
    }
}
