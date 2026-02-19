<?php

namespace Tests\Unit\Http;

use App\Domain\Entity\Cliente\RepositorioInterface as ClienteRepositorio;
use App\Exception\DomainHttpException;
use App\Http\ClienteApi;
use App\Infrastructure\Controller\Cliente as ClienteController;
use App\Infrastructure\Presenter\HttpJsonPresenter;
use Faker\Factory as FakerFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ClienteApiTest extends TestCase
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
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $api = new ClienteApi($controller, $presenter, $repositorio);

        $this->assertInstanceOf(ClienteApi::class, $api);
        $this->assertSame($controller, $api->controller);
        $this->assertInstanceOf(HttpJsonPresenter::class, $api->presenter);
        $this->assertSame($repositorio, $api->repositorio);
    }

    public function testCreateClienteSuccess()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $clienteData = [
            'nome' => $this->faker->name,
            'documento' => $this->faker->cpf(false),
            'email' => $this->faker->unique()->safeEmail,
            'fone' => $this->faker->cellphoneNumber(false),
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'documento', 'email', 'fone'])
            ->andReturn($clienteData);

        $controller->shouldReceive('useRepositorio')
            ->with($repositorio)
            ->andReturn($controller);
        $controller->shouldReceive('criar')
            ->with($clienteData['nome'], $clienteData['documento'], $clienteData['email'], $clienteData['fone'])
            ->andReturn(['uuid' => $this->faker->uuid]);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('uuid', $data);
    }

    public function testCreateClienteValidationFails()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'documento', 'email', 'fone'])
            ->andReturn(['nome' => '']);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertStringContainsString('campo nome é obrigatório', $data['msg']);
    }

    public function testCreateClienteDomainException()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $clienteData = [
            'nome' => $this->faker->name,
            'documento' => 'invalid-doc',
            'email' => $this->faker->unique()->safeEmail,
            'fone' => $this->faker->cellphoneNumber(false),
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'documento', 'email', 'fone'])
            ->andReturn($clienteData);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('criar')->andThrow(new DomainHttpException('Documento inválido', Response::HTTP_BAD_REQUEST));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Documento inválido', $data['msg']);
    }

    public function testCreateClienteGenericException()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $clienteData = [
            'nome' => $this->faker->name,
            'documento' => $this->faker->cpf(false),
            'email' => $this->faker->unique()->safeEmail,
            'fone' => $this->faker->cellphoneNumber(false),
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'documento', 'email', 'fone'])
            ->andReturn($clienteData);

        $exceptionMessage = 'Erro interno';
        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('criar')->andThrow(new \Exception($exceptionMessage));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals($exceptionMessage, $data['msg']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testCastsUpdate()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $api = new ClienteApi($controller, $presenter, $repositorio);

        $dados = [
            'nome' => 'João Silva',
            'documento' => null,
            'email' => 'joao@email.com',
            'fone' => '11987654321'
        ];

        $result = $api->castsUpdate($dados);

        $this->assertEquals('João Silva', $result['nome']);
        $this->assertEquals('joao@email.com', $result['email']);
        $this->assertEquals('11987654321', $result['fone']);
        $this->assertArrayNotHasKey('documento', $result); // Null removido
    }

    public function testReadClientesSuccess()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);

        $expectedData = [['nome' => 'João', 'email' => 'joao@email.com'], ['nome' => 'Maria', 'email' => 'maria@email.com']];

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('listar')->andReturn($expectedData);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testReadClientesDomainException()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('listar')->andThrow(new DomainHttpException('Erro ao listar', Response::HTTP_INTERNAL_SERVER_ERROR));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro ao listar', $data['msg']);
    }

    public function testReadOneClienteSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $expectedData = ['uuid' => $uuid, 'nome' => 'João', 'email' => 'joao@email.com'];

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('obterUm')->with($uuid)->andReturn($expectedData);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($uuid, $data['uuid']);
    }

    public function testReadOneClienteValidationFails()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => 'invalid-uuid']);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }

    public function testUpdateClienteSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $updateData = ['nome' => 'João Silva Atualizado'];
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'nome', 'documento', 'email', 'fone'])
            ->andReturn(['uuid' => $uuid, 'nome' => 'João Silva Atualizado']);

        $expectedResponse = ['uuid' => $uuid, 'nome' => 'João Silva Atualizado'];

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('atualizar')->with($uuid, ['uuid' => $uuid, 'nome' => 'João Silva Atualizado'])->andReturn($expectedResponse);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($uuid, $data['uuid']);
    }

    public function testDeleteClienteSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('deletar')->with($uuid)->andReturn(true);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteClienteDomainException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('deletar')->andThrow(new DomainHttpException('Cliente não encontrado', Response::HTTP_NOT_FOUND));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Cliente não encontrado', $data['msg']);
    }

    public function testDeleteClienteGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('deletar')->andThrow(new \Exception('Erro interno'));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testCastsUpdateFormatadoDocumento()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $api = new ClienteApi($controller, $presenter, $repositorio);

        $dados = [
            'documento' => '123.456.789-01',
            'fone'      => null,
        ];

        $result = $api->castsUpdate($dados);

        $this->assertEquals('12345678901', $result['documento']);
        $this->assertArrayNotHasKey('fone', $result);
    }

    public function testCastsUpdateFormatadoFone()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $api = new ClienteApi($controller, $presenter, $repositorio);

        $dados = [
            'fone' => '(11) 98765-4321',
            'nome' => 'João Silva',
        ];

        $result = $api->castsUpdate($dados);

        $this->assertEquals('11987654321', $result['fone']);
        $this->assertEquals('João Silva', $result['nome']);
    }

    public function testReadClientesGenericException()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('listar')->andThrow(new \Exception('Erro interno'));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testReadOneClienteGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('obterUm')->andThrow(new \Exception('Erro interno'));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testReadOneClienteRetornaNull()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('obterUm')->with($uuid)->andReturn(null);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->readOne($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUpdateClienteValidationFails()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'nome', 'documento', 'email', 'fone'])
            ->andReturn(['uuid' => 'invalid-uuid']);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }

    public function testUpdateClienteDomainException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'nome', 'documento', 'email', 'fone'])
            ->andReturn(['uuid' => $uuid, 'nome' => 'João Silva']);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('atualizar')->andThrow(new DomainHttpException('Cliente não encontrado', Response::HTTP_NOT_FOUND));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Cliente não encontrado', $data['msg']);
    }

    public function testUpdateClienteGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['uuid', 'nome', 'documento', 'email', 'fone'])
            ->andReturn(['uuid' => $uuid, 'nome' => 'João Silva']);

        $controller->shouldReceive('useRepositorio')->andReturn($controller);
        $controller->shouldReceive('atualizar')->andThrow(new \Exception('Erro interno'));

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testDeleteClienteValidationFails()
    {
        $controller = Mockery::mock(ClienteController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(ClienteRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => 'invalid-uuid']);

        $api = new ClienteApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }
}