<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Dto;

use App\Domain\Entity\Usuario\Entidade as UsuarioEntidade;
use App\Infrastructure\Dto\AuthenticatedDto;
use PHPUnit\Framework\TestCase;

class AuthenticatedDtoTest extends TestCase
{
    private function criarEntidadeMock(): UsuarioEntidade
    {
        $senhaHash = password_hash('senha123', PASSWORD_BCRYPT);

        return new UsuarioEntidade(
            uuid: 'uuid-usuario-123',
            nome: 'Usuário Teste',
            email: 'teste@email.com',
            senha: $senhaHash,
            ativo: true,
            perfil: 'atendente',
            criadoEm: new \DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_construtor_atribui_propriedades_corretamente(): void
    {
        $usuario = $this->criarEntidadeMock();
        $dto = new AuthenticatedDto($usuario, 'jwt-token-123', 'Bearer');

        $this->assertSame($usuario, $dto->usuario);
        $this->assertEquals('jwt-token-123', $dto->token);
        $this->assertEquals('Bearer', $dto->tokenType);
    }

    public function test_to_associative_array_retorna_estrutura_correta(): void
    {
        $usuario = $this->criarEntidadeMock();
        $dto = new AuthenticatedDto($usuario, 'jwt-token-123', 'Bearer');

        $resultado = $dto->toAssociativeArray();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('user', $resultado);
        $this->assertArrayHasKey('token', $resultado);
        $this->assertArrayHasKey('token_type', $resultado);
        $this->assertEquals('jwt-token-123', $resultado['token']);
        $this->assertEquals('Bearer', $resultado['token_type']);
        $this->assertIsArray($resultado['user']);
    }
}
