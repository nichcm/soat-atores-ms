<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Cliente;

use App\Domain\UseCase\Cliente\ReadUseCase;
use App\Infrastructure\Gateway\ClienteGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(ClienteGateway::class);
    }

    public function test_retorna_lista_de_clientes(): void
    {
        $this->gatewayMock
            ->expects($this->once())
            ->method('listar')
            ->willReturn([
                [
                    'uuid'          => 'uuid-1',
                    'nome'          => 'Cliente Um',
                    'documento'     => '11111111111',
                    'email'         => 'um@email.com',
                    'fone'          => '11111111111',
                    'criado_em'     => '2024-01-01 10:00:00',
                    'atualizado_em' => '2024-01-01 10:00:00',
                ],
                [
                    'uuid'          => 'uuid-2',
                    'nome'          => 'Cliente Dois',
                    'documento'     => '22222222222',
                    'email'         => 'dois@email.com',
                    'fone'          => '22222222222',
                    'criado_em'     => '2024-02-01 10:00:00',
                    'atualizado_em' => '2024-02-01 10:00:00',
                ],
            ]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertArrayHasKey('uuid', $resultado[0]);
        $this->assertArrayHasKey('nome', $resultado[0]);
        $this->assertEquals('uuid-1', $resultado[0]['uuid']);
    }

    public function test_retorna_lista_vazia_quando_nao_ha_clientes(): void
    {
        $this->gatewayMock
            ->expects($this->once())
            ->method('listar')
            ->willReturn([]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertIsArray($resultado);
        $this->assertCount(0, $resultado);
    }

    public function test_mapeia_entidades_para_http_response(): void
    {
        $this->gatewayMock
            ->method('listar')
            ->willReturn([
                [
                    'uuid'          => 'uuid-abc',
                    'nome'          => 'Nome Teste',
                    'documento'     => '33333333333',
                    'email'         => 'teste@email.com',
                    'fone'          => '33333333333',
                    'criado_em'     => '2024-03-01 10:00:00',
                    'atualizado_em' => '2024-03-01 10:00:00',
                ],
            ]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertArrayHasKey('criado_em', $resultado[0]);
        $this->assertArrayHasKey('atualizado_em', $resultado[0]);
    }
}
