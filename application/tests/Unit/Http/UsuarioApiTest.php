<?php

namespace Tests\Unit\Http;

use App\Domain\Entity\Usuario\RepositorioInterface as UsuarioRepositorio;
use App\Exception\DomainHttpException;
use App\Http\UsuarioApi;
use App\Infrastructure\Controller\Usuario as UsuarioController;
use App\Infrastructure\Presenter\HttpJsonPresenter;
use Faker\Factory as FakerFactory;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UsuarioApiTest extends TestCase
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
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $api = new UsuarioApi($controller, $presenter, $repositorio);

        $this->assertInstanceOf(UsuarioApi::class, $api);
        $this->assertSame($controller, $api->controller);
        $this->assertInstanceOf(HttpJsonPresenter::class, $api->presenter);
        $this->assertSame($repositorio, $api->repositorio);
    }

    public function testCreateUsuarioSuccess()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $usuarioData = [
            'nome' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'senha' => $this->faker->password(8),
            'perfil' => 'atendente',
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'email', 'senha', 'perfil'])
            ->andReturn($usuarioData);

        $controller->shouldReceive('criar')
            ->with(Mockery::type('App\\Infrastructure\\Dto\\UsuarioDto'), $repositorio)
            ->andReturn(['uuid' => $this->faker->uuid]);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('uuid', $data);
    }

    public function testCreateUsuarioValidationFails()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'email', 'senha', 'perfil'])
            ->andReturn(['nome' => '']);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertStringContainsString('campo nome é obrigatório', $data['msg']);
    }

    public function testCreateUsuarioDomainException()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $usuarioData = [
            'nome' => $this->faker->name,
            'email' => 'invalid-email',
            'senha' => $this->faker->password(8),
            'perfil' => 'atendente',
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'email', 'senha', 'perfil'])
            ->andReturn($usuarioData);

        $controller->shouldReceive('criar')->andThrow(new DomainHttpException('Usuário inválido', Response::HTTP_BAD_REQUEST));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('O campo email deve ser um endereço de e-mail válido.', $data['msg']);
    }

    public function testCreateUsuarioGenericException()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $usuarioData = [
            'nome' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'senha' => $this->faker->password(8),
            'perfil' => 'atendente',
        ];

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('only')
            ->with(['nome', 'email', 'senha', 'perfil'])
            ->andReturn($usuarioData);

        $exceptionMessage = 'Erro interno';
        $controller->shouldReceive('criar')->andThrow(new \Exception($exceptionMessage));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->create($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals($exceptionMessage, $data['msg']);
    }

    public function testReadUsuariosSuccess()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);

        $expectedData = [['nome' => 'João', 'email' => 'joao@email.com'], ['nome' => 'Maria', 'email' => 'maria@email.com']];

        $controller->shouldReceive('listar')->with($repositorio)->andReturn($expectedData);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testReadUsuariosDomainException()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);

        $controller->shouldReceive('listar')->andThrow(new DomainHttpException('Erro ao listar', Response::HTTP_INTERNAL_SERVER_ERROR));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro ao listar', $data['msg']);
    }

    public function testReadUsuariosGenericException()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);

        $controller->shouldReceive('listar')->andThrow(new \Exception('Erro interno'));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->read($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testUpdateUsuarioSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['nome', 'uuid'])
            ->andReturn(['uuid' => $uuid, 'nome' => 'João Silva Atualizado']);

        $expectedResponse = ['uuid' => $uuid, 'nome' => 'João Silva Atualizado'];

        $controller->shouldReceive('atualizar')->with($uuid, ['nome' => 'João Silva Atualizado'], $repositorio)->andReturn($expectedResponse);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($uuid, $data['uuid']);
    }

    public function testUpdateUsuarioValidationFails()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')->with(['nome', 'uuid'])->andReturn(['uuid' => 'invalid-uuid']);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }

    public function testUpdateUsuarioDomainException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['nome', 'uuid'])
            ->andReturn(['uuid' => $uuid, 'nome' => 'João Silva']);

        $controller->shouldReceive('atualizar')->andThrow(new DomainHttpException('Usuário inválido', Response::HTTP_BAD_REQUEST));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Usuário inválido', $data['msg']);
    }

    public function testUpdateUsuarioGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')
            ->with(['nome', 'uuid'])
            ->andReturn(['uuid' => $uuid, 'nome' => 'João Silva']);

        $controller->shouldReceive('atualizar')->andThrow(new \Exception('Erro interno'));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->update($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testDeleteUsuarioSuccess()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('deletar')->with($uuid, $repositorio)->andReturn(true);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteUsuarioDomainException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('deletar')->andThrow(new DomainHttpException('Usuário não encontrado', Response::HTTP_NOT_FOUND));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Usuário não encontrado', $data['msg']);
    }

    public function testDeleteUsuarioGenericException()
    {
        $uuid = $this->faker->uuid;
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn($uuid);
        $request->shouldReceive('merge')->with(['uuid' => $uuid])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => $uuid]);

        $controller->shouldReceive('deletar')->andThrow(new \Exception('Erro interno'));

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
        $this->assertEquals('Erro interno', $data['msg']);
    }

    public function testDeleteUsuarioValidationFails()
    {
        $controller = Mockery::mock(UsuarioController::class);
        $presenter = new HttpJsonPresenter();
        $repositorio = Mockery::mock(UsuarioRepositorio::class);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->with('uuid')->andReturn('invalid-uuid');
        $request->shouldReceive('merge')->with(['uuid' => 'invalid-uuid'])->andReturn($request);
        $request->shouldReceive('only')->with(['uuid'])->andReturn(['uuid' => 'invalid-uuid']);

        $api = new UsuarioApi($controller, $presenter, $repositorio);
        $response = $api->delete($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['err']);
    }
}