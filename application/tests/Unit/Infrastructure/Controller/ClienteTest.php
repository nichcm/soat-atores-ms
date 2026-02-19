<?php

namespace Tests\Unit\Infrastructure\Controller;

use Tests\TestCase;
use Mockery;
use App\Infrastructure\Controller\Cliente as ClienteController;
use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepositorio;
use App\Domain\Entity\Cliente\Entidade as ClienteEntidade;
use App\Exception\DomainHttpException;
use DateTimeImmutable;

class ClienteTest extends TestCase
{
    protected ClienteRepositorio $repositorioMock;
    protected ClienteController $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositorioMock = Mockery::mock(ClienteRepositorio::class);
        $this->controller = new ClienteController();
        $this->controller->useRepositorio($this->repositorioMock);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCriarClienteComSucesso()
    {
        $nome = 'John Doe';
        $documento = '12345678901';
        $email = 'john.doe@example.com';
        $fone = '11999999999';

        $this->repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->with($documento, 'documento')->andReturn(null);
        $this->repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->with($email, 'email')->andReturn(null);
        $this->repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->with($fone, 'fone')->andReturn(null);
        $this->repositorioMock->shouldReceive('criar')->andReturn([
            'uuid'          => 'some-uuid',
            'nome'          => $nome,
            'documento'     => $documento,
            'email'         => $email,
            'fone'          => $fone,
            'criado_em'     => '2024-01-01 10:00:00',
            'atualizado_em' => '2024-01-01 10:00:00',
            'deletado_em'   => null,
        ]);

        $resultado = $this->controller->criar($nome, $documento, $email, $fone);

        $this->assertIsArray($resultado);
        $this->assertEquals($nome, $resultado['nome']);
    }

    public function testCriarClienteSemRepositorioLancaExcecao()
    {
        $this->expectException(\Error::class);

        $controller = new ClienteController(); // Sem useRepositorio
        $controller->criar('nome', 'documento', 'email', 'fone');
    }

    public function testListarClientes()
    {
        $this->repositorioMock->shouldReceive('listar')->andReturn([
            [
                'uuid'          => 'uuid-1',
                'nome'          => 'Cliente 1',
                'documento'     => '12345678901',
                'email'         => 'cliente1@email.com',
                'fone'          => '11999999999',
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ],
        ]);

        $resultado = $this->controller->listar();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    public function testObterUmClienteComSucesso()
    {
        $uuid = 'some-uuid';
        $clienteEntidadeMock = Mockery::mock(ClienteEntidade::class);
        $clienteEntidadeMock->shouldReceive('toHttpResponse')->andReturn(['uuid' => $uuid]);

        $this->repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->with($uuid, 'uuid')->andReturn($clienteEntidadeMock);

        $resultado = $this->controller->obterUm($uuid);

        $this->assertIsArray($resultado);
        $this->assertEquals($uuid, $resultado['uuid']);
    }

    public function testAtualizarClienteComSucesso()
    {
        $uuid = 'some-uuid';
        $dados = ['nome' => 'Jane Doe'];

        $entidadeExistente = new ClienteEntidade(
            uuid: $uuid,
            nome: 'John Doe',
            documento: '12345678901',
            email: 'john@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );

        $this->repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->with($uuid, 'uuid')->andReturn($entidadeExistente);
        $this->repositorioMock->shouldReceive('atualizar')->andReturn([
            'uuid'          => $uuid,
            'nome'          => 'Jane Doe',
            'documento'     => '12345678901',
            'email'         => 'john@email.com',
            'fone'          => '11999999999',
            'criado_em'     => '2024-01-01 10:00:00',
            'atualizado_em' => '2024-06-01 12:00:00',
            'deletado_em'   => null,
        ]);

        $resultado = $this->controller->atualizar($uuid, $dados);

        $this->assertIsArray($resultado);
        $this->assertEquals('Jane Doe', $resultado['nome']);
    }

    public function testDeletarClienteComSucesso()
    {
        $uuid = 'some-uuid';

        $clienteEntidadeMock = Mockery::mock(ClienteEntidade::class);

        $this->repositorioMock->shouldReceive('encontrarPorIdentificadorUnico')->with($uuid, 'uuid')->andReturn($clienteEntidadeMock);
        $this->repositorioMock->shouldReceive('deletar')->andReturn(true);

        $resultado = $this->controller->deletar($uuid);

        $this->assertTrue($resultado);
    }
}
