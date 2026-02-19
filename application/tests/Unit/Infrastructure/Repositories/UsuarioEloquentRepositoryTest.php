<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Domain\Entity\Usuario\Entidade;
use App\Infrastructure\Repositories\UsuarioEloquentRepository;
use App\Models\UsuarioModel;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class UsuarioEloquentRepositoryTest extends TestCase
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
        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $usuarioModelInstance = Mockery::mock(UsuarioModel::class);

        $usuarioModelInstance->shouldReceive('getAttribute')->with('uuid')->andReturn($uuid);
        $usuarioModelInstance->shouldReceive('getAttribute')->with('nome')->andReturn('João Silva');
        $usuarioModelInstance->shouldReceive('getAttribute')->with('email')->andReturn('joao@email.com');
        $usuarioModelInstance->shouldReceive('getAttribute')->with('senha')->andReturn('hashed_password');
        $usuarioModelInstance->shouldReceive('getAttribute')->with('ativo')->andReturn(true);
        $usuarioModelInstance->shouldReceive('getAttribute')->with('perfil')->andReturn('atendente');
        $usuarioModelInstance->shouldReceive('getAttribute')->with('criado_em')->andReturn('2024-01-01 10:00:00');
        $usuarioModelInstance->shouldReceive('getAttribute')->with('atualizado_em')->andReturn('2024-01-01 10:00:00');
        $usuarioModelInstance->shouldReceive('getAttribute')->with('deletado_em')->andReturn(null);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(true);
        $builderMock->shouldReceive('first')->andReturn($usuarioModelInstance);

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->encontrarPorIdentificadorUnico($uuid);

        $this->assertInstanceOf(Entidade::class, $result);
        $this->assertEquals($uuid, $result->uuid);
        $this->assertEquals('João Silva', $result->nome);
    }

    public function testEncontrarPorIdentificadorUnicoComUuidInexistente()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('exists')->andReturn(false);

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->encontrarPorIdentificadorUnico($uuid);

        $this->assertNull($result);
    }

    public function testCriarUsuario()
    {
        $dados = [
            'nome' => 'Maria Silva',
            'email' => 'maria@email.com',
            'senha' => 'password123',
            'perfil' => 'atendente',
        ];

        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $usuarioModelInstance = Mockery::mock(UsuarioModel::class);
        
        $uuid = $this->faker->uuid;
        $hashedPassword = 'hashed_password_123';
        
        $expectedData = [
            'uuid' => Mockery::any(),
            'nome' => 'Maria Silva',
            'email' => 'maria@email.com',
            'senha' => Mockery::any(),
            'ativo' => true,
            'perfil' => 'atendente'
        ];
        
        $returnData = [...$expectedData, 'uuid' => $uuid, 'senha' => $hashedPassword, 'id' => 1, 'criado_em' => '2024-01-01 10:00:00', 'atualizado_em' => '2024-01-01 10:00:00'];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('create')->with(Mockery::on(function ($args) use ($dados) {
            return $args['nome'] === $dados['nome'] &&
                   $args['email'] === $dados['email'] &&
                   $args['perfil'] === $dados['perfil'] &&
                   $args['ativo'] === true &&
                   is_string($args['uuid']) &&
                   is_string($args['senha']);
        }))->andReturn($usuarioModelInstance);
        $usuarioModelInstance->shouldReceive('refresh')->andReturn($usuarioModelInstance);
        $usuarioModelInstance->shouldReceive('toArray')->andReturn($returnData);

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->criar($dados);

        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('Maria Silva', $result['nome']);
    }

    public function testListarUsuarios()
    {
        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $collectionMock = Mockery::mock(Collection::class);

        $expectedData = [
            ['uuid' => 'uuid-1', 'nome' => 'Usuario 1'],
            ['uuid' => 'uuid-2', 'nome' => 'Usuario 2']
        ];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('ativo', Entidade::STATUS_ATIVO)->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('deletado_em', null)->andReturn($builderMock);
        $builderMock->shouldReceive('get')->with(['*'])->andReturn($collectionMock);
        $collectionMock->shouldReceive('toArray')->andReturn($expectedData);

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->listar();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Usuario 1', $result[0]['nome']);
    }

    public function testDeletarUsuarioComSucesso()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('delete')->andReturn(1); // 1 registro deletado

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->deletar($uuid);

        $this->assertTrue($result);
    }

    public function testDeletarUsuarioQuandoNaoEncontrado()
    {
        $uuid = $this->faker->uuid;
        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('delete')->andReturn(0); // 0 registros deletados

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->deletar($uuid);

        $this->assertFalse($result);
    }

    public function testAtualizarUsuario()
    {
        $uuid = $this->faker->uuid;
        $novosDados = ['nome' => 'Nome Atualizado'];
        
        $modelMock = Mockery::mock(UsuarioModel::class);
        $builderMock = Mockery::mock(Builder::class);
        $usuarioModelInstance = Mockery::mock(UsuarioModel::class);

        $returnData = [
            'uuid' => $uuid,
            'nome' => 'Nome Atualizado',
            'email' => 'email@test.com',
            'ativo' => true,
            'perfil' => 'atendente',
            'atualizado_em' => '2024-01-01 11:00:00'
        ];

        $modelMock->shouldReceive('query')->andReturn($builderMock);
        $builderMock->shouldReceive('where')->with('uuid', $uuid)->andReturn($builderMock);
        $builderMock->shouldReceive('first')->andReturn($usuarioModelInstance);
        $usuarioModelInstance->shouldReceive('update')->with(['nome' => 'Nome Atualizado']);
        $usuarioModelInstance->shouldReceive('refresh')->andReturn($usuarioModelInstance);
        $usuarioModelInstance->shouldReceive('toArray')->andReturn($returnData);

        $repository = new UsuarioEloquentRepository($modelMock);
        $result = $repository->atualizar($uuid, $novosDados);

        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals('Nome Atualizado', $result['nome']);
    }
}