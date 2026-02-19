<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Cliente;

use App\Domain\Entity\Cliente\Entidade;
use App\Domain\UseCase\Cliente\UpdateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\ClienteGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(ClienteGateway::class);
    }

    private function criarEntidadeFake(array $overrides = []): Entidade
    {
        $dados = array_merge([
            'uuid'         => 'uuid-cliente-123',
            'nome'         => 'Carlos Lima',
            'documento'    => '12345678901',
            'email'        => 'carlos@email.com',
            'fone'         => '11988887777',
        ], $overrides);

        return new Entidade(
            uuid: $dados['uuid'],
            nome: $dados['nome'],
            documento: $dados['documento'],
            email: $dados['email'],
            fone: $dados['fone'],
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_atualiza_cliente_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarEntidadeFake());

        $this->gatewayMock
            ->method('atualizar')
            ->willReturn([
                'uuid'          => 'uuid-cliente-123',
                'nome'          => 'Carlos Lima Atualizado',
                'documento'     => '12345678901',
                'email'         => 'carlos@email.com',
                'fone'          => '11988887777',
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-06-01 10:00:00',
                'deletado_em'   => null,
            ]);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $resultado = $useCase->exec('uuid-cliente-123', ['nome' => 'Carlos Lima Atualizado']);

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('Carlos Lima Atualizado', $resultado->nome);
    }

    public function test_lanca_excecao_quando_uuid_vazio(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('identificador único não informado');
        $this->expectExceptionCode(400);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('', ['nome' => 'Novo Nome']);
    }

    public function test_lanca_excecao_quando_cliente_nao_encontrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Não encontrado(a)');
        $this->expectExceptionCode(404);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('uuid-inexistente', ['nome' => 'Novo Nome']);
    }

    public function test_lanca_excecao_quando_atualizacao_retorna_vazio(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarEntidadeFake());

        $this->gatewayMock
            ->method('atualizar')
            ->willReturn([]);

        $this->expectException(\TypeError::class);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('uuid-cliente-123', ['nome' => 'Novo Nome']);
    }
}
