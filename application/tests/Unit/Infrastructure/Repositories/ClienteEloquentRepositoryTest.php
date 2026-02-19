<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Domain\Entity\Cliente\Entidade;
use App\Infrastructure\Repositories\ClienteEloquentRepository;
use App\Models\ClienteModel;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ClienteEloquentRepositoryTest extends TestCase
{
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = FakerFactory::create('pt_BR');
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testEncontrarPorIdentificadorUnicoComUuidExistente()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $clienteModelInstance = Mockery::mock(ClienteModel::class);

        $clienteModelInstance->shouldReceive('getAttribute')->with('uuid')->andReturn($uuid);
        $clienteModelInstance->shouldReceive('getAttribute')->with('nome')->andReturn('João Silva');
        $clienteModelInstance->shouldReceive('getAttribute')->with('documento')->andReturn('12345678901');
        $clienteModelInstance->shouldReceive('getAttribute')->with('email')->andReturn('joao@email.com');
        $clienteModelInstance->shouldReceive('getAttribute')->with('fone')->andReturn('11999999999');
        $clienteModelInstance->shouldReceive('getAttribute')->with('criado_em')->andReturn('2024-01-01 10:00:00');
        $clienteModelInstance->shouldReceive('getAttribute')->with('atualizado_em')->andReturn('2024-01-01 10:00:00');
        $clienteModelInstance->shouldReceive('getAttribute')->with('deletado_em')->andReturn(null);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(true);
        $builderMock->shouldReceive('first')->andReturn($clienteModelInstance);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->encontrarPorIdentificadorUnico($uuid);

        $this->assertInstanceOf(Entidade::class, $result);
        $this->assertEquals($uuid, $result->uuid);
        $this->assertEquals('João Silva', $result->nome);
    }

    public function testEncontrarPorIdentificadorUnicoComUuidInexistente()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(false);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->encontrarPorIdentificadorUnico($uuid);

        $this->assertNull($result);
    }

    public function testCriarCliente()
    {
        $dados = [
            'nome' => 'Maria Silva',
            'documento' => '98765432100',
            'email' => 'maria@email.com',
            'fone' => '11888888888',
        ];

        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $clienteModelInstance = Mockery::mock(ClienteModel::class);
        
        $uuid = $this->faker->uuid;

        $expectedData = [...$dados, 'uuid' => Mockery::any()];
        $returnData = [...$dados, 'uuid' => $uuid, 'id' => 1, 'criado_em' => '2024-01-01 10:00:00', 'atualizado_em' => '2024-01-01 10:00:00'];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('create')->with(Mockery::subset($dados))->andReturn($clienteModelInstance);
        $clienteModelInstance->shouldReceive('refresh')->andReturn($clienteModelInstance);
        $clienteModelInstance->shouldReceive('toArray')->andReturn($returnData);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->criar($dados);

        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('Maria Silva', $result['nome']);
    }

    public function testListarClientes()
    {
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $collectionMock = Mockery::mock(Collection::class);

        $expectedData = [
            ['uuid' => 'uuid-1', 'nome' => 'Cliente 1'],
            ['uuid' => 'uuid-2', 'nome' => 'Cliente 2']
        ];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('deletado_em', null)->andReturn($builderMock);
        $builderMock->shouldReceive('get')->with(['*'])->andReturn($collectionMock);
        $collectionMock->shouldReceive('toArray')->andReturn($expectedData);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->listar();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Cliente 1', $result[0]['nome']);
    }

    public function testDeletarClienteComSucesso()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('delete')->andReturn(1); // 1 registro deletado

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->deletar($uuid);

        $this->assertTrue($result);
    }

    public function testDeletarClienteQuandoNaoEncontrado()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('delete')->andReturn(0); // 0 registros deletados

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->deletar($uuid);

        $this->assertFalse($result);
    }

    public function testAtualizarCliente()
    {
        $uuid = $this->faker->uuid;
        $novosDados = ['nome' => 'Nome Atualizado'];
        
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $clienteModelInstance = Mockery::mock(ClienteModel::class);

        $returnData = [
            'uuid' => $uuid,
            'nome' => 'Nome Atualizado',
            'documento' => '12345678901',
            'email' => 'email@test.com',
            'fone' => '11999999999',
            'atualizado_em' => '2024-01-01 11:00:00'
        ];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('first')->andReturn($clienteModelInstance);
        $clienteModelInstance->shouldReceive('update')->with($novosDados);
        $clienteModelInstance->shouldReceive('refresh')->andReturn($clienteModelInstance);
        $clienteModelInstance->shouldReceive('toArray')->andReturn($returnData);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->atualizar($uuid, $novosDados);

        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('Nome Atualizado', $result['nome']);
    }

    public function testObterIdNumericoComUuidExistente()
    {
        $uuid = $this->faker->uuid;
        $expectedId = 42;
        
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $clienteModelInstance = Mockery::mock(ClienteModel::class);

        $clienteModelInstance->shouldReceive('getAttribute')->with('id')->andReturn($expectedId);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(true);
        $builderMock->shouldReceive('first')->andReturn($clienteModelInstance);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->obterIdNumerico($uuid);

        $this->assertEquals($expectedId, $result);
    }

    public function testObterIdNumericoComUuidInexistente()
    {
        $uuid = $this->faker->uuid;
        
        $modelMock = Mockery::mock(ClienteModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(false);

        $repository = new ClienteEloquentRepository($modelMock);
        $result = $repository->obterIdNumerico($uuid);

        $this->assertEquals(-1, $result);
    }
}