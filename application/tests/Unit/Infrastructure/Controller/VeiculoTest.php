<?php

namespace Tests\Unit\Infrastructure\Controller;

use Tests\TestCase;
use Mockery;
use App\Infrastructure\Controller\Veiculo as VeiculoController;
use App\Domain\Entity\Veiculo\RepositorioInterface as VeiculoRepositorio;
use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepositorio;
use App\Domain\Entity\Veiculo\Entidade as VeiculoEntidade;
use App\Domain\Entity\Cliente\Entidade as ClienteEntidade;
use App\Exception\DomainHttpException;
use DateTimeImmutable;

class VeiculoTest extends TestCase
{
    private int $anoValido;

    public function setUp(): void
    {
        parent::setUp();
        $this->anoValido = (int) date('Y');
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function criarVeiculoEntidade(string $uuid = 'veiculo-uuid', string $placa = 'ABC1234'): VeiculoEntidade
    {
        return new VeiculoEntidade(
            uuid: $uuid,
            marca: 'Fiat',
            modelo: 'Uno',
            placa: $placa,
            ano: $this->anoValido,
            clienteId: 1,
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    private function criarClienteEntidade(string $uuid = 'cliente-uuid'): ClienteEntidade
    {
        return new ClienteEntidade(
            uuid: $uuid,
            nome: 'Dono Veiculo',
            documento: '12345678901',
            email: 'dono@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function testCriarVeiculoComSucesso()
    {
        $clienteUuid = 'cliente-uuid';
        $placa = 'ABC1234';

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($placa, 'placa')
            ->andReturn(null);
        $veiculoRepositorioMock->shouldReceive('criar')
            ->andReturn([
                'uuid'          => 'veiculo-uuid',
                'marca'         => 'Fiat',
                'modelo'        => 'Uno',
                'placa'         => $placa,
                'ano'           => $this->anoValido,
                'cliente_id'    => 1,
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ]);

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);
        $clienteRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($clienteUuid, 'uuid')
            ->andReturn($this->criarClienteEntidade($clienteUuid));
        $clienteRepositorioMock->shouldReceive('obterIdNumerico')
            ->with($clienteUuid)
            ->andReturn(1);

        $controller = new VeiculoController();
        $controller->useRepositorio($veiculoRepositorioMock);
        $controller->useClienteRepositorio($clienteRepositorioMock);

        $resultado = $controller->criar('Fiat', 'Uno', $placa, $this->anoValido, $clienteUuid);

        $this->assertIsArray($resultado);
        $this->assertEquals($placa, $resultado['placa']);
    }

    public function testCriarVeiculoSemRepositorioLancaExcecao()
    {
        $this->expectException(\Error::class);

        $controller = new VeiculoController();
        $controller->useClienteRepositorio(Mockery::mock(ClienteRepositorio::class));
        $controller->criar('Fiat', 'Uno', 'ABC1234', $this->anoValido, 'cliente-uuid');
    }

    public function testCriarVeiculoSemClienteRepositorioLancaExcecao()
    {
        $this->expectException(\Error::class);

        $controller = new VeiculoController();
        $controller->useRepositorio(Mockery::mock(VeiculoRepositorio::class));
        $controller->criar('Fiat', 'Uno', 'ABC1234', $this->anoValido, 'cliente-uuid');
    }

    public function testListarVeiculos()
    {
        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('listar')
            ->andReturn([
                [
                    'uuid'          => 'veiculo-uuid-1',
                    'marca'         => 'Fiat',
                    'modelo'        => 'Uno',
                    'placa'         => 'ABC1234',
                    'ano'           => $this->anoValido,
                    'cliente_id'    => 1,
                    'criado_em'     => '2024-01-01 10:00:00',
                    'atualizado_em' => '2024-01-01 10:00:00',
                ],
            ]);

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $controller = new VeiculoController();
        $controller->useRepositorio($veiculoRepositorioMock);
        $controller->useClienteRepositorio($clienteRepositorioMock);

        $resultado = $controller->listar();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    public function testObterUmVeiculoComSucesso()
    {
        $uuid = 'veiculo-uuid';

        $veiculoEntidadeMock = Mockery::mock(VeiculoEntidade::class);
        $veiculoEntidadeMock->shouldReceive('toHttpResponse')
            ->andReturn(['uuid' => $uuid, 'placa' => 'ABC1234']);

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($veiculoEntidadeMock);

        $clienteRepositorioMock = Mockery::mock(ClienteRepositorio::class);

        $controller = new VeiculoController();
        $controller->useRepositorio($veiculoRepositorioMock);
        $controller->useClienteRepositorio($clienteRepositorioMock);

        $resultado = $controller->obterUm($uuid);

        $this->assertIsArray($resultado);
        $this->assertEquals($uuid, $resultado['uuid']);
    }

    public function testAtualizarVeiculoComSucesso()
    {
        $uuid = 'veiculo-uuid';
        $dados = ['modelo' => 'Palio'];

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($this->criarVeiculoEntidade($uuid));
        $veiculoRepositorioMock->shouldReceive('atualizar')
            ->andReturn([
                'uuid'          => $uuid,
                'marca'         => 'Fiat',
                'modelo'        => 'Palio',
                'placa'         => 'ABC1234',
                'ano'           => $this->anoValido,
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-06-01 12:00:00',
                'deletado_em'   => null,
            ]);

        $controller = new VeiculoController();
        $controller->useRepositorio($veiculoRepositorioMock);

        $resultado = $controller->atualizar($uuid, $dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('Palio', $resultado['modelo']);
    }

    public function testDeletarVeiculoComSucesso()
    {
        $uuid = 'veiculo-uuid';

        $veiculoEntidadeMock = Mockery::mock(VeiculoEntidade::class);

        $veiculoRepositorioMock = Mockery::mock(VeiculoRepositorio::class);
        $veiculoRepositorioMock->shouldReceive('encontrarPorIdentificadorUnico')
            ->with($uuid, 'uuid')
            ->andReturn($veiculoEntidadeMock);
        $veiculoRepositorioMock->shouldReceive('deletar')
            ->andReturn(true);

        $controller = new VeiculoController();
        $controller->useRepositorio($veiculoRepositorioMock);

        $resultado = $controller->deletar($uuid);

        $this->assertTrue($resultado);
    }
}
