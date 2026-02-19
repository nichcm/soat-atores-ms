<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\UseCase\Veiculo;

use App\Domain\Entity\Cliente\Entidade as ClienteEntidade;
use App\Domain\Entity\Veiculo\Entidade as VeiculoEntidade;
use App\Domain\UseCase\Veiculo\CreateUseCase;
use App\Exception\DomainHttpException;
use App\Infrastructure\Gateway\ClienteGateway;
use App\Infrastructure\Gateway\VeiculoGateway;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUseCaseTest extends TestCase
{
    private MockObject $veiculoGatewayMock;
    private MockObject $clienteGatewayMock;
    private int $anoValido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->veiculoGatewayMock = $this->createMock(VeiculoGateway::class);
        $this->clienteGatewayMock = $this->createMock(ClienteGateway::class);
        $this->anoValido = (int) date('Y');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function criarClienteFake(): ClienteEntidade
    {
        return new ClienteEntidade(
            uuid: 'uuid-cliente-dono',
            nome: 'Dono do Veículo',
            documento: '12345678901',
            email: 'dono@email.com',
            fone: '11999999999',
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    private function criarVeiculoFake(): VeiculoEntidade
    {
        return new VeiculoEntidade(
            uuid: 'uuid-veiculo-existente',
            marca: 'Ford',
            modelo: 'Ka',
            placa: 'ABC1234',
            ano: $this->anoValido,
            clienteId: 1,
            criadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function test_cria_veiculo_com_sucesso(): void
    {
        $this->clienteGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarClienteFake());

        $this->clienteGatewayMock
            ->method('obterIdNumerico')
            ->willReturn(42);

        $this->veiculoGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->veiculoGatewayMock
            ->method('criar')
            ->willReturn([
                'uuid'          => 'uuid-novo-veiculo',
                'marca'         => 'Honda',
                'modelo'        => 'Civic',
                'placa'         => 'XYZ9999',
                'ano'           => $this->anoValido,
                'cliente_id'    => 42,
                'criado_em'     => '2024-01-01 10:00:00',
                'atualizado_em' => '2024-01-01 10:00:00',
            ]);

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'XYZ9999',
            ano: $this->anoValido,
            clienteUuid: 'uuid-cliente-dono',
        );

        $resultado = $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);

        $this->assertInstanceOf(VeiculoEntidade::class, $resultado);
        $this->assertEquals('uuid-novo-veiculo', $resultado->uuid);
        $this->assertEquals('Honda', $resultado->marca);
    }

    public function test_lanca_excecao_quando_cliente_uuid_vazio(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Cliente dono do veículo não informado');
        $this->expectExceptionCode(400);

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'XYZ9999',
            ano: $this->anoValido,
            clienteUuid: '',
        );

        $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);
    }

    public function test_lanca_excecao_quando_cliente_nao_encontrado(): void
    {
        $this->clienteGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Cliente não encontrado');
        $this->expectExceptionCode(404);

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'XYZ9999',
            ano: $this->anoValido,
            clienteUuid: 'uuid-inexistente',
        );

        $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);
    }

    public function test_lanca_excecao_quando_id_numerico_invalido(): void
    {
        $this->clienteGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarClienteFake());

        $this->clienteGatewayMock
            ->method('obterIdNumerico')
            ->willReturn(-1);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Cliente não encontrado');
        $this->expectExceptionCode(404);

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'XYZ9999',
            ano: $this->anoValido,
            clienteUuid: 'uuid-cliente-dono',
        );

        $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);
    }

    public function test_lanca_excecao_quando_placa_ja_cadastrada(): void
    {
        $this->clienteGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarClienteFake());

        $this->clienteGatewayMock
            ->method('obterIdNumerico')
            ->willReturn(42);

        $this->veiculoGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarVeiculoFake());

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Placa não pode ser cadastrada pois já existe um veículo com essa placa');
        $this->expectExceptionCode(400);

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'ABC1234',
            ano: $this->anoValido,
            clienteUuid: 'uuid-cliente-dono',
        );

        $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);
    }

    public function test_ano_futuro_lanca_excecao(): void
    {
        $this->clienteGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn($this->criarClienteFake());

        $this->clienteGatewayMock
            ->method('obterIdNumerico')
            ->willReturn(42);

        $this->veiculoGatewayMock
            ->method('encontrarPorIdentificadorUnico')
            ->willReturn(null);

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Ano não pode ser maior que o ano atual');

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'XYZ9999',
            ano: $this->anoValido + 2,
            clienteUuid: 'uuid-cliente-dono',
        );

        $useCase->exec($this->veiculoGatewayMock, $this->clienteGatewayMock);
    }

    public function test_lanca_excecao_de_dominio_quando_criar_retorna_nao_array(): void
    {
        $clienteGatewayMock = Mockery::mock(ClienteGateway::class);
        $clienteGatewayMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn($this->criarClienteFake());
        $clienteGatewayMock->shouldReceive('obterIdNumerico')->andReturn(42);

        $veiculoGatewayMock = Mockery::mock(VeiculoGateway::class);
        $veiculoGatewayMock->shouldReceive('encontrarPorIdentificadorUnico')->andReturn(null);
        $veiculoGatewayMock->shouldReceive('criar')->andThrow(new DomainHttpException('Erro ao cadastrar', 500));

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Erro ao cadastrar');
        $this->expectExceptionCode(500);

        $useCase = new CreateUseCase(
            marca: 'Honda',
            modelo: 'Civic',
            placa: 'XYZ9999',
            ano: $this->anoValido,
            clienteUuid: 'uuid-cliente-dono',
        );

        $useCase->exec($veiculoGatewayMock, $clienteGatewayMock);
    }
}
