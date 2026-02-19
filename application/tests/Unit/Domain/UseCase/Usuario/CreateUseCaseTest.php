<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Usuario;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\UseCase\Usuario\CreateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\UsuarioGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;
    private string $senhaHash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(UsuarioGateway::class);
        $this->senhaHash = password_hash('senha123', PASSWORD_BCRYPT);
    }

    public function test_cria_usuario_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->gatewayMock
            ->method('criar')
            ->willReturn(['uuid' => 'uuid-novo-usuario']);

        $useCase = new CreateUseCase(
            nome: 'João Alves',
            email: 'joao@email.com',
            senha: $this->senhaHash,
            perfil: 'atendente',
        );

        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('uuid-novo-usuario', $resultado->uuid);
        $this->assertEquals('João Alves', $resultado->nome);
    }

    public function test_lanca_excecao_quando_email_ja_cadastrado(): void
    {
        $entidadeFake = new Entidade(
            uuid: 'uuid-existente',
            nome: 'Usuário Existente',
            email: 'joao@email.com',
            senha: $this->senhaHash,
            ativo: true,
            perfil: 'atendente',
            criadoEm: new \DateTimeImmutable('2024-01-01'),
            atualizadoEm: new \DateTimeImmutable('2024-01-01'),
        );

        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($entidadeFake);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('E-mail já cadastrado');
        $this->expectExceptionCode(400);

        $useCase = new CreateUseCase(
            nome: 'João Alves',
            email: 'joao@email.com',
            senha: $this->senhaHash,
            perfil: 'atendente',
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
            nome: 'João Alves',
            email: 'joao@email.com',
            senha: $this->senhaHash,
            perfil: 'atendente',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_email_invalido_lanca_excecao_na_entidade(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email inválido');

        $useCase = new CreateUseCase(
            nome: 'João Alves',
            email: 'email-invalido',
            senha: $this->senhaHash,
            perfil: 'atendente',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_perfil_invalido_lanca_excecao_na_entidade(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Perfil inválido');

        $useCase = new CreateUseCase(
            nome: 'João Alves',
            email: 'joao@email.com',
            senha: $this->senhaHash,
            perfil: 'perfil_inexistente',
        );

        $useCase->exec($this->gatewayMock);
    }

    public function test_usuario_criado_tem_ativo_true(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->gatewayMock
            ->method('criar')
            ->willReturn(['uuid' => 'uuid-novo']);

        $useCase = new CreateUseCase(
            nome: 'João Alves',
            email: 'joao@email.com',
            senha: $this->senhaHash,
            perfil: 'atendente',
        );

        $resultado = $useCase->exec($this->gatewayMock);

        $this->assertTrue($resultado->ativo);
    }
}
