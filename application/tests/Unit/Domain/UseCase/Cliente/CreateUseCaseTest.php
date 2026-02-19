<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Cliente;

use App\Domain\Entity\Cliente\Entidade;
use App\Domain\UseCase\Cliente\CreateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\ClienteGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUseCaseTest extends TestCase
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
            uuid: 'uuid-retornado',
            nome: 'Maria Silva',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_cria_cliente_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->gatewayMock
            ->method('criar')
            ->willReturn([
                'uuid'         => 'uuid-novo',
                'nome'         => 'Maria Silva',
                'documento'    => '12345678901',
                'email'        => 'maria@email.com',
                'fone'         => '11999999999',
                'criado_em'    => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ]);

        $useCase = new CreateUseCase(
            nome: 'Maria Silva',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
        );

        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('uuid-novo', $resultado->uuid);
        $this->assertEquals('Maria Silva', $resultado->nome);
    }

    public function test_lanca_excecao_quando_documento_ja_cadastrado(): void
    {
        $this->gatewayMock
            ->expects($this->once())
            ->method('encontrarPorIdentificadorUnico')
            ->with($this->anything(), 'documento')
            ->willReturn($this->criarEntidadeFake());

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Cliente com documento repetido');

        $useCase = new CreateUseCase(
            nome: 'Maria Silva',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_lanca_excecao_quando_fone_ja_cadastrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturnCallback(function ($identificador, $nomeIdentificador) {
                if ($nomeIdentificador === 'documento') {
                    return null;
                }
                if ($nomeIdentificador === 'fone') {
                    return $this->criarEntidadeFake();
                }
                return null;
            });

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Fone já cadastrado');

        $useCase = new CreateUseCase(
            nome: 'Maria Silva',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_lanca_excecao_quando_email_ja_cadastrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturnCallback(function ($identificador, $nomeIdentificador) {
                if ($nomeIdentificador === 'email') {
                    return $this->criarEntidadeFake();
                }
                return null;
            });

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('E-mail já cadastrado');

        $useCase = new CreateUseCase(
            nome: 'Maria Silva',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_lanca_excecao_quando_cadastro_falha(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->gatewayMock
            ->method('criar')
            ->willReturn([]);

        $this->expectException(\TypeError::class);

        $useCase = new CreateUseCase(
            nome: 'Maria Silva',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_nome_invalido_lanca_excecao_na_entidade(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $useCase = new CreateUseCase(
            nome: 'AB',
            documento: '12345678901',
            email: 'maria@email.com',
            fone: '11999999999',
        );

        $useCase->exec($this->gatewayMock);
    }
}
