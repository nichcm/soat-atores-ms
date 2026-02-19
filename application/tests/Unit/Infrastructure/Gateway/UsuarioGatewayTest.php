<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Gateway;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\Entity\Usuario\RepositorioInterface;
use App\Infrastructure\Gateway\UsuarioGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UsuarioGatewayTest extends TestCase
{
    private MockObject $repositorioMock;
    private UsuarioGateway $gateway;
    private string $senhaHash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositorioMock = $this->createMock(RepositorioInterface::class);
        $this->gateway = new UsuarioGateway($this->repositorioMock);
        $this->senhaHash = password_hash('senha123', PASSWORD_BCRYPT);
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

    public function test_encontrar_por_identificador_unico_delega_ao_repositorio(): void
    {
        $entidade = $this->criarEntidadeFake();

        $this->repositorioMock
            ->method('encontrarPorIdentificadorUnico')
            ->with('uuid-usuario-123', 'uuid')
            ->willReturn($entidade);

        $resultado = $this->gateway->encontrarPorIdentificadorUnico('uuid-usuario-123', 'uuid');

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('uuid-usuario-123', $resultado->uuid);
    }

    public function test_encontrar_por_identificador_unico_retorna_null_quando_nao_encontrado(): void
    {
        $this->repositorioMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $resultado = $this->gateway->encontrarPorIdentificadorUnico('inexistente@email.com', 'email');

        $this->assertNull($resultado);
    }

    public function test_criar_delega_ao_repositorio(): void
    {
        $dados = ['nome' => 'João', 'email' => 'joao@test.com', 'senha' => $this->senhaHash, 'perfil' => 'atendente'];
        $retorno = array_merge(['uuid' => 'novo-uuid'], $dados);

        $this->repositorioMock
            ->method('criar')
            ->with($dados)
            ->willReturn($retorno);

        $resultado = $this->gateway->criar($dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('novo-uuid', $resultado['uuid']);
    }

    public function test_listar_delega_ao_repositorio_com_colunas_filtradas(): void
    {
        $colunasEsperadas = ['uuid', 'nome', 'email', 'ativo', 'criado_em', 'atualizado_em'];

        $this->repositorioMock
            ->method('listar')
            ->with($colunasEsperadas)
            ->willReturn([['uuid' => 'uuid-1', 'nome' => 'Usuário 1']]);

        $resultado = $this->gateway->listar();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    public function test_deletar_delega_ao_repositorio(): void
    {
        $this->repositorioMock
            ->method('deletar')
            ->with('uuid-usuario-123')
            ->willReturn(true);

        $resultado = $this->gateway->deletar('uuid-usuario-123');

        $this->assertTrue($resultado);
    }

    public function test_atualizar_delega_ao_repositorio(): void
    {
        $dados = ['nome' => 'Nome Atualizado'];
        $retorno = ['uuid' => 'uuid-usuario-123', 'nome' => 'Nome Atualizado'];

        $this->repositorioMock
            ->method('atualizar')
            ->with('uuid-usuario-123', $dados)
            ->willReturn($retorno);

        $resultado = $this->gateway->atualizar('uuid-usuario-123', $dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('Nome Atualizado', $resultado['nome']);
    }
}
