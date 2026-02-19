<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Gateway;

use App\Domain\Entity\Cliente\Entidade;
use App\Domain\Entity\Cliente\RepositorioInterface;
use App\Infrastructure\Gateway\ClienteGateway;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClienteGatewayTest extends TestCase
{
    private MockObject $repositorioMock;
    private ClienteGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositorioMock = $this->createMock(RepositorioInterface::class);
        $this->gateway = new ClienteGateway($this->repositorioMock);
    }

    private function criarEntidadeFake(): Entidade
    {
        return new Entidade(
            uuid: 'uuid-cliente-123',
            nome: 'Carlos Lima',
            documento: '12345678901',
            email: 'carlos@email.com',
            fone: '11988887777',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_encontrar_por_identificador_unico_delega_ao_repositorio(): void
    {
        $entidade = $this->criarEntidadeFake();

        $this->repositorioMock
            ->method('encontrarPorIdentificadorUnico')
            ->with('uuid-cliente-123', 'uuid')
            ->willReturn($entidade);

        $resultado = $this->gateway->encontrarPorIdentificadorUnico('uuid-cliente-123', 'uuid');

        $this->assertInstanceOf(Entidade::class, $resultado);
        $this->assertEquals('uuid-cliente-123', $resultado->uuid);
    }

    public function test_encontrar_por_identificador_unico_retorna_null_quando_nao_encontrado(): void
    {
        $this->repositorioMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $resultado = $this->gateway->encontrarPorIdentificadorUnico('uuid-inexistente', 'uuid');

        $this->assertNull($resultado);
    }

    public function test_criar_delega_ao_repositorio(): void
    {
        $dados = ['nome' => 'João', 'documento' => '12345678901', 'email' => 'joao@test.com', 'fone' => '11999999999'];
        $retorno = array_merge(['uuid' => 'novo-uuid'], $dados);

        $this->repositorioMock
            ->method('criar')
            ->with($dados)
            ->willReturn($retorno);

        $resultado = $this->gateway->criar($dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('novo-uuid', $resultado['uuid']);
    }

    public function test_listar_delega_ao_repositorio(): void
    {
        $this->repositorioMock
            ->method('listar')
            ->with(['*'])
            ->willReturn([['uuid' => 'uuid-1', 'nome' => 'Cliente 1']]);

        $resultado = $this->gateway->listar();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    public function test_deletar_delega_ao_repositorio(): void
    {
        $this->repositorioMock
            ->method('deletar')
            ->with('uuid-cliente-123')
            ->willReturn(true);

        $resultado = $this->gateway->deletar('uuid-cliente-123');

        $this->assertTrue($resultado);
    }

    public function test_atualizar_delega_ao_repositorio(): void
    {
        $dados = ['nome' => 'Nome Atualizado'];
        $retorno = ['uuid' => 'uuid-cliente-123', 'nome' => 'Nome Atualizado'];

        $this->repositorioMock
            ->method('atualizar')
            ->with('uuid-cliente-123', $dados)
            ->willReturn($retorno);

        $resultado = $this->gateway->atualizar('uuid-cliente-123', $dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('Nome Atualizado', $resultado['nome']);
    }

    public function test_obter_id_numerico_delega_ao_repositorio(): void
    {
        $this->repositorioMock
            ->method('obterIdNumerico')
            ->with('uuid-cliente-123')
            ->willReturn(42);

        $resultado = $this->gateway->obterIdNumerico('uuid-cliente-123');

        $this->assertEquals(42, $resultado);
    }
}
