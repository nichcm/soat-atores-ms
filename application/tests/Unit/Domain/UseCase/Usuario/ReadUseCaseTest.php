<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Usuario;

use App\Domain\UseCase\Usuario\ReadUseCase;
use App\Infrastructure\Gateway\UsuarioGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(UsuarioGateway::class);
    }

    public function test_retorna_lista_de_usuarios(): void
    {
        $listaFake = [
            ['uuid' => 'uuid-1', 'nome' => 'Usuário Um', 'email' => 'um@email.com'],
            ['uuid' => 'uuid-2', 'nome' => 'Usuário Dois', 'email' => 'dois@email.com'],
        ];

        $this->gatewayMock
            ->expects($this->once())
            ->method('listar')
            ->willReturn($listaFake);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('uuid-1', $resultado[0]['uuid']);
    }

    public function test_retorna_lista_vazia(): void
    {
        $this->gatewayMock
            ->method('listar')
            ->willReturn([]);

        $useCase = new ReadUseCase();
        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}
