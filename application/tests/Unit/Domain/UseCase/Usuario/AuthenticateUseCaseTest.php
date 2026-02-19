<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Usuario;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\UseCase\Usuario\AuthenticateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Dto\AuthenticatedDto;
use App\Infrastructure\Gateway\UsuarioGateway;
use App\Signature\AuthServiceInterface;
use App\Signature\TokenServiceInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticateUseCaseTest extends TestCase
{
    private MockObject $authServiceMock;
    private MockObject $tokenServiceMock;
    private MockObject $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authServiceMock = $this->createMock(AuthServiceInterface::class);
        $this->tokenServiceMock = $this->createMock(TokenServiceInterface::class);
        $this->gatewayMock = $this->createMock(UsuarioGateway::class);
    }

    private function criarUsuarioFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-usuario-auth',
            nome: 'Usuário Auth',
            email: 'auth@email.com',
            senha: password_hash('senha123', PASSWORD_BCRYPT),
            ativo: true,
            perfil: 'comercial',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_autentica_usuario_com_sucesso(): void
    {
        $usuario = $this->criarUsuarioFake();

        $this->authServiceMock
            ->expects($this->once())
            ->method('attempt')
            ->with('auth@email.com', 'senha123')
            ->willReturn($usuario);

        $this->tokenServiceMock
            ->expects($this->once())
            ->method('generate')
            ->willReturn('token.jwt.gerado');

        $useCase = new AuthenticateUseCase($this->authServiceMock, $this->tokenServiceMock);
        $resultado = $useCase->exec('auth@email.com', 'senha123', $this->gatewayMock);

        $this->assertInstanceOf(AuthenticatedDto::class, $resultado);
        $this->assertEquals('token.jwt.gerado', $resultado->token);
        $this->assertEquals('Bearer', $resultado->tokenType);
        $this->assertSame($usuario, $resultado->usuario);
    }

    public function test_lanca_excecao_quando_credenciais_invalidas(): void
    {
        $this->authServiceMock
            ->expects($this->once())
            ->method('attempt')
            ->with('invalido@email.com', 'senha-errada')
            ->willReturn(null);

        $this->tokenServiceMock
            ->expects($this->never())
            ->method('generate');

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Credenciais inválidas');
        $this->expectExceptionCode(401);

        $useCase = new AuthenticateUseCase($this->authServiceMock, $this->tokenServiceMock);
        $useCase->exec('invalido@email.com', 'senha-errada', $this->gatewayMock);
    }

    public function test_token_payload_usa_dados_do_usuario(): void
    {
        $usuario = $this->criarUsuarioFake();
        $payloadEsperado = ['sub' => 'uuid-usuario-auth', 'perf' => 'comercial'];

        $this->authServiceMock
            ->method('attempt')
            ->willReturn($usuario);

        $this->tokenServiceMock
            ->expects($this->once())
            ->method('generate')
            ->with($payloadEsperado)
            ->willReturn('token.com.payload');

        $useCase = new AuthenticateUseCase($this->authServiceMock, $this->tokenServiceMock);
        $resultado = $useCase->exec('auth@email.com', 'senha123', $this->gatewayMock);

        $this->assertEquals('token.com.payload', $resultado->token);
    }
}
