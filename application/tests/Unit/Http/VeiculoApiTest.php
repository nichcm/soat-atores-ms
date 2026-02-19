<?php

namespace Tests\Unit\Http;

use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepositorio;
use App\Domain\Entity\Veiculo\RepositorioInterface as VeiculoRepositorio;
use App\Exception\DomainHttpException;
use App\Http\VeiculoApi;
use App\Infrastructure\Controller\Veiculo as VeiculoController;
use App\Infrastructure\Presenter\HttpJsonPresenter;
use Faker\Factory as FakerFactory;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VeiculoApiTest extends TestCase
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

    public function testConstructor()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);

        $this->assertInstanceOf(VeiculoApi::class, $api);
        $this->assertSame($controller, $api->controller);
        $this->assertInstanceOf(HttpJsonPresenter::class, $api->presenter);
        $this->assertSame($veiculoRepositorio, $api->repositorio);
        $this->assertSame($clienteRepositorio, $api->clienteRepositorio);
    }

    public function testCreateVeiculoSuccess()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $veiculoData = [
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'placa' => 'ABC1234',
            'ano' => 2023,
            'cliente_uuid' => $this->faker->uuid,
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['marca', 'modelo', 'placa', 'ano', 'cliente_uuid'])
            ->andReturn($veiculoData);

        $controller->shouldReceive('useRepositorio')
            ->with($veiculoRepositorio)
            ->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')
            ->with($clienteRepositorio)
            ->andReturn($controller);
        $controller->shouldReceive('criar')
            ->with($veiculoData['marca'], $veiculoData['modelo'], $veiculoData['placa'], $veiculoData['ano'], $veiculoData['cliente_uuid'])
            ->andReturn(['uuid' => $this->faker->uuid]);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('uuid', $data);
    }

    public function testCreateVeiculoValidationFails()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['marca', 'modelo', 'placa', 'ano', 'cliente_uuid'])
            ->andReturn(['marca' => '']);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertStringContainsString('campo marca é obrigatório', $data['msg']);
    }

    public function testCreateVeiculoDomainException()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $veiculoData = [
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'placa' => 'ABC1234',
            'ano' => 2030,
            'cliente_uuid' => $this->faker->uuid,
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['marca', 'modelo', 'placa', 'ano', 'cliente_uuid'])
            ->andReturn($veiculoData);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('criar')->andThrow(new DomainHttpException('Ano futuro não permitido', Response::HTTP_BAD_REQUEST));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Ano futuro não permitido', $data['msg']);
    }

    public function testCreateVeiculoGenericException()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $veiculoData = [
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'placa' => 'ABC1234',
            'ano' => 2023,
            'cliente_uuid' => $this->faker->uuid,
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['marca', 'modelo', 'placa', 'ano', 'cliente_uuid'])
            ->andReturn($veiculoData);

        $exceptionMessage = 'Erro interno';
        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('criar')->andThrow(new \Exception($exceptionMessage));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals($exceptionMessage, $data['msg']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testCastsUpdate()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);

        $dados = [
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'placa' => null,
            'ano' => 2023
        ];

        $result = $api->castsUpdate($dados);

        $this->assertEquals('Honda', $result['marca']);
        $this->assertEquals('Civic', $result['modelo']);
        $this->assertEquals(2023, $result['ano']);
        $this->assertArrayNotHasKey('placa', $result); // Null removido
    }

    public function testReadVeiculosSuccess()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);

        $expectedData = [['marca' => 'Honda', 'modelo' => 'Civic'], ['marca' => 'Toyota', 'modelo' => 'Corolla']];

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('listar')->andReturn($expectedData);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testReadVeiculosDomainException()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('listar')->andThrow(new DomainHttpException('Erro ao listar', Response::HTTP_INTERNAL_SERVER_ERROR));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro ao listar', $data['msg']);
    }

    public function testReadOneVeiculoSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $expectedData = ['uuid' => $uuid, 'marca' => 'Honda', 'modelo' => 'Civic'];

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('obterUm')->with($uuid)->andReturn($expectedData);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($uuid, $data['uuid']);
    }

    public function testReadOneVeiculoValidationFails()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => 'invalid-uuid']);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }

    public function testUpdateVeiculoSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'marca', 'modelo', 'placa', 'ano'])
            ->andReturn(['uuid' => $uuid, 'marca' => 'Honda Atualizada']);

        $expectedResponse = ['uuid' => $uuid, 'marca' => 'Honda Atualizada'];

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('atualizar')->with($uuid, ['uuid' => $uuid, 'marca' => 'Honda Atualizada'])->andReturn($expectedResponse);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($uuid, $data['uuid']);
    }

    public function testDeleteVeiculoSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('deletar')->with($uuid)->andReturn(true);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteVeiculoDomainException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('deletar')->andThrow(new DomainHttpException('Veículo não encontrado', Response::HTTP_NOT_FOUND));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Veículo não encontrado', $data['msg']);
    }

    public function testDeleteVeiculoGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('deletar')->andThrow(new \Exception('Erro interno'));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testReadVeiculosGenericException()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('listar')->andThrow(new \Exception('Erro interno'));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testReadOneVeiculoGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('obterUm')->andThrow(new \Exception('Erro interno'));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testReadOneVeiculoRetornaNull()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('obterUm')->with($uuid)->andReturn(null);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUpdateVeiculoValidationFails()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'marca', 'modelo', 'placa', 'ano'])
            ->andReturn(['uuid' => 'invalid-uuid', 'marca' => 'Honda']);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }

    public function testUpdateVeiculoDomainException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'marca', 'modelo', 'placa', 'ano'])
            ->andReturn(['uuid' => $uuid, 'marca' => 'Honda']);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('atualizar')->andThrow(new DomainHttpException('Veículo não encontrado', Response::HTTP_NOT_FOUND));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Veículo não encontrado', $data['msg']);
    }

    public function testUpdateVeiculoGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'marca', 'modelo', 'placa', 'ano'])
            ->andReturn(['uuid' => $uuid, 'marca' => 'Honda']);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('useClienteRepositorio')->andReturn($controller);
        $controller->shouldReceive('atualizar')->andThrow(new \Exception('Erro interno'));

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testDeleteVeiculoValidationFails()
    {
        $controller = Mockery::mock(VeiculoController::class);
        $presenter = new HttpJsonPresenter();
        $veiculoRepositorio = Mockery::mock(VeiculoRepositorio::class);
        $clienteRepositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => 'invalid-uuid']);

        $api = new VeiculoApi($controller, $presenter, $veiculoRepositorio, $clienteRepositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }
}