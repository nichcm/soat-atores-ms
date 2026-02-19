<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Domain\Entity\Veiculo\Entidade;
use App\Infrastructure\Repositories\VeiculoEloquentRepository;
use App\Models\VeiculoModel;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class VeiculoEloquentRepositoryTest extends TestCase
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
        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $veiculoModelInstance = Mockery::mock(VeiculoModel::class);

        $veiculoModelInstance->shouldReceive('getAttribute')->with('uuid')->andReturn($uuid);
        $veiculoModelInstance->shouldReceive('getAttribute')->with('marca')->andReturn('Honda');
        $veiculoModelInstance->shouldReceive('getAttribute')->with('modelo')->andReturn('Civic');
        $veiculoModelInstance->shouldReceive('getAttribute')->with('placa')->andReturn('ABC1234');
        $veiculoModelInstance->shouldReceive('getAttribute')->with('ano')->andReturn(2023);
        $veiculoModelInstance->shouldReceive('getAttribute')->with('cliente_id')->andReturn(1);
        $veiculoModelInstance->shouldReceive('getAttribute')->with('criado_em')->andReturn('2024-01-01 10:00:00');
        $veiculoModelInstance->shouldReceive('getAttribute')->with('atualizado_em')->andReturn('2024-01-01 10:00:00');
        $veiculoModelInstance->shouldReceive('getAttribute')->with('deletado_em')->andReturn(null);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(true);
        $builderMock->shouldReceive('first')->andReturn($veiculoModelInstance);

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->encontrarPorIdentificadorUnico($uuid);

        $this->assertInstanceOf(Entidade::class, $result);
        $this->assertEquals($uuid, $result->uuid);
        $this->assertEquals('Honda', $result->marca);
        $this->assertEquals('Civic', $result->modelo);
    }

    public function testEncontrarPorIdentificadorUnicoComUuidInexistente()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(false);

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->encontrarPorIdentificadorUnico($uuid);

        $this->assertNull($result);
    }

    public function testCriarVeiculo()
    {
        $dados = [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'placa' => 'XYZ9876',
            'ano' => 2023,
            'cliente_id' => 1,
        ];

        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $veiculoModelInstance = Mockery::mock(VeiculoModel::class);
        
        $uuid = $this->faker->uuid;

        $expectedData = [...$dados, 'uuid' => Mockery::any()];
        $returnData = [...$dados, 'uuid' => $uuid, 'id' => 1, 'criado_em' => '2024-01-01 10:00:00', 'atualizado_em' => '2024-01-01 10:00:00'];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('create')->with(Mockery::subset($dados))->andReturn($veiculoModelInstance);
        $veiculoModelInstance->shouldReceive('refresh')->andReturn($veiculoModelInstance);
        $veiculoModelInstance->shouldReceive('toArray')->andReturn($returnData);

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->criar($dados);

        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('Toyota', $result['marca']);
        $this->assertEquals('Corolla', $result['modelo']);
    }

    public function testListarVeiculos()
    {
        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $collectionMock = Mockery::mock(Collection::class);

        $expectedData = [
            ['uuid' => 'uuid-1', 'marca' => 'Honda', 'modelo' => 'Civic'],
            ['uuid' => 'uuid-2', 'marca' => 'Toyota', 'modelo' => 'Corolla']
        ];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('deletado_em', null)->andReturn($builderMock);
        $builderMock->shouldReceive('get')->with(['*'])->andReturn($collectionMock);
        $collectionMock->shouldReceive('toArray')->andReturn($expectedData);

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->listar();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Honda', $result[0]['marca']);
    }

    public function testDeletarVeiculoComSucesso()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('delete')->andReturn(1); // 1 registro deletado

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->deletar($uuid);

        $this->assertTrue($result);
    }

    public function testDeletarVeiculoQuandoNaoEncontrado()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('delete')->andReturn(0); // 0 registros deletados

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->deletar($uuid);

        $this->assertFalse($result);
    }

    public function testAtualizarVeiculo()
    {
        $uuid = $this->faker->uuid;
        $novosDados = ['marca' => 'Marca Atualizada', 'modelo' => 'Modelo Atualizado'];
        
        $modelMock = Mockery::mock(VeiculoModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $veiculoModelInstance = Mockery::mock(VeiculoModel::class);

        $returnData = [
            'uuid' => $uuid,
            'marca' => 'Marca Atualizada',
            'modelo' => 'Modelo Atualizado',
            'placa' => 'ABC1234',
            'ano' => 2023,
            'atualizado_em' => '2024-01-01 11:00:00'
        ];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('first')->andReturn($veiculoModelInstance);
        $veiculoModelInstance->shouldReceive('update')->with($novosDados);
        $veiculoModelInstance->shouldReceive('refresh')->andReturn($veiculoModelInstance);
        $veiculoModelInstance->shouldReceive('toArray')->andReturn($returnData);

        $repository = new VeiculoEloquentRepository($modelMock);
        $result = $repository->atualizar($uuid, $novosDados);

        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('Marca Atualizada', $result['marca']);
        $this->assertEquals('Modelo Atualizado', $result['modelo']);
    }
}