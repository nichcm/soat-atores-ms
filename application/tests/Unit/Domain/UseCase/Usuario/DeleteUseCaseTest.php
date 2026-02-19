<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Usuario;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\UseCase\Usuario\DeleteUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\UsuarioGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(UsuarioGateway::class);
    }

    private function criarEntidadeFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-para-deletar',
            nome: 'Usuário Deletar',
            email: 'deletar@email.com',
            senha: password_hash('senha', PASSWORD_BCRYPT),
            ativo: true,
            perfil: 'mecanico',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_deleta_usuario_com_sucesso(): void
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

    public function test_lanca_excecao_quando_usuario_nao_encontrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->gatewayMock
            ->expects($this->never())
            ->method('deletar');

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Usuário não encontrado');
        $this->expectExceptionCode(400);

        $useCase = new DeleteUseCase($this->gatewayMock);
        $useCase->exec('uuid-inexistente');
    }
}
