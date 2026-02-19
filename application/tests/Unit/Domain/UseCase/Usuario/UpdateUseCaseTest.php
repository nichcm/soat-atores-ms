<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Usuario;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\UseCase\Usuario\UpdateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\UsuarioGateway;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateUseCaseTest extends TestCase
{
    private MockObject $gatewayMock;
    private string $senhaHash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = $this->createMock(UsuarioGateway::class);
        $this->senhaHash = password_hash('senha123', PASSWORD_BCRYPT);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function criarEntidadeFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-usuario-123',
            nome: 'Usuário Teste',
            email: 'teste@email.com',
            senha: $this->senhaHash,
            ativo: true,
            perfil: 'atendente',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_atualiza_usuario_com_sucesso(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarEntidadeFake());

        $this->gatewayMock
            ->method('atualizar')
            ->willReturn([
                'uuid'          => 'uuid-usuario-123',
                'nome'          => 'Usuário Atualizado',
                'email'         => 'teste@email.com',
                'senha'         => $this->senhaHash,
                'ativo'         => true,
                'perfil'        => 'atendente',
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-06-01 12:00:00',
                'deletado_em'   => null,
            ]);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $resultado = $useCase->exec('uuid-usuario-123', ['nome' => 'Usuário Atualizado']);

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('Usuário Atualizado', $resultado->nome);
    }

    public function test_lanca_excecao_quando_uuid_vazio(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('identificador único não informado');
        $this->expectExceptionCode(400);

        $useCase = new UpdateUseCase($this->gatewayMock);
        $useCase->exec('', ['nome' => 'Novo Nome']);
    }

    public function test_lanca_excecao_quando_usuario_nao_encontrado(): void
    {
        $this->gatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Usuário não encontrado');
        $this->expectExceptionCode(400);

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
        $useCase->exec('uuid-usuario-123', ['nome' => 'Novo Nome']);
    }

    public function test_lanca_excecao_de_dominio_quando_atualizar_retorna_nao_array(): void
    {
        $gatewayMock = Mockery::mock(UsuarioGateway::class);
        $gatewayMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn($this->criarEntidadeFake());
        $gatewayMock->shouldReceive('atualizar')->andThrow(new DomainHttpException('Erro ao atualizar usuário', 500));

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Erro ao atualizar usuário');
        $this->expectExceptionCode(500);

        $useCase = new UpdateUseCase($gatewayMock);
        $useCase->exec('uuid-usuario-123', ['nome' => 'Novo Nome']);
    }
}
