<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Usuario;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\Entity\Usuario\Mapper;
use App\Infrastructure\Dto\UsuarioDto;
use App\Models\UsuarioModel;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new Mapper();
    }

    private function criarModelFake(array $dados = []): UsuarioModel
    {
        $defaults = [
            'uuid'          => 'uuid-usuario-abc',
            'nome'          => 'Roberto Silva',
            'email'         => 'roberto@email.com',
            'senha'         => password_hash('senha123', PASSWORD_BCRYPT),
            'ativo'         => true,
            'perfil'        => 'mecanico',
            'criado_em'     => '2024-02-10 08:00:00',
            'atualizado_em' => '2024-02-10 08:00:00',
            'deletado_em'   => null,
        ];

        $model = new UsuarioModel();

        foreach (array_merge($defaults, $dados) as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    public function test_from_model_to_entity_mapeia_campos_basicos(): void
    {
        $model = $this->criarModelFake();

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertInstanceOf(Entidade::class, $entidade);
        $this->assertEquals('uuid-usuario-abc', $entidade->uuid);
        $this->assertEquals('Roberto Silva', $entidade->nome);
        $this->assertEquals('roberto@email.com', $entidade->email);
        $this->assertEquals('mecanico', $entidade->perfil);
        $this->assertTrue($entidade->ativo);
    }

    public function test_from_model_to_entity_deletado_em_null(): void
    {
        $model = $this->criarModelFake(['deletado_em' => null]);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertNull($entidade->deletadoEm);
    }

    public function test_from_model_to_entity_deletado_em_preenchido(): void
    {
        $model = $this->criarModelFake(['deletado_em' => '2024-09-20 16:00:00']);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertNotNull($entidade->deletadoEm);
    }

    public function test_from_entity_to_model_mapeia_campos(): void
    {
        $entidade = new Entidade(
            uuid: 'uuid-entidade-123',
            nome: 'Fernanda Lima',
            email: 'fernanda@email.com',
            senha: 'hash-da-senha',
            ativo: true,
            perfil: 'comercial',
            criadoEm: new DateTimeImmutable('2024-03-01 10:00:00'),
            atualizadoEm: new DateTimeImmutable('2024-03-01 10:00:00'),
        );

        $model = $this->mapper->fromEntityToModel($entidade);

        $this->assertInstanceOf(UsuarioModel::class, $model);
        $this->assertEquals('uuid-entidade-123', $model->uuid);
        $this->assertEquals('Fernanda Lima', $model->nome);
        $this->assertEquals('fernanda@email.com', $model->email);
        $this->assertEquals('hash-da-senha', $model->senha);
        $this->assertTrue($model->ativo);
        $this->assertEquals('comercial', $model->perfil);
    }

    public function test_from_model_to_array_retorna_campos_corretos(): void
    {
        $model = $this->criarModelFake();

        $array = $this->mapper->fromModelToArray($model);

        $this->assertArrayHasKey('uuid', $array);
        $this->assertArrayHasKey('nome', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('senha', $array);
        $this->assertArrayHasKey('ativo', $array);
        $this->assertArrayHasKey('perfil', $array);
        $this->assertArrayHasKey('criado_em', $array);
        $this->assertArrayHasKey('atualizado_em', $array);
        $this->assertArrayHasKey('deletado_em', $array);
    }

    public function test_from_array_to_model_mapeia_campos(): void
    {
        $array = [
            'uuid'          => 'uuid-array-123',
            'nome'          => 'Paulo Salave',
            'email'         => 'paulo@email.com',
            'senha'         => 'senha-hash',
            'ativo'         => false,
            'perfil'        => 'gestor_estoque',
            'criado_em'     => '2024-01-15 08:00:00',
            'atualizado_em' => '2024-01-15 08:00:00',
            'deletado_em'   => null,
        ];

        $model = $this->mapper->fromArrayToModel($array);

        $this->assertInstanceOf(UsuarioModel::class, $model);
        $this->assertEquals('uuid-array-123', $model->uuid);
        $this->assertEquals('Paulo Salave', $model->nome);
        $this->assertEquals('paulo@email.com', $model->email);
        $this->assertFalse($model->ativo);
        $this->assertEquals('gestor_estoque', $model->perfil);
    }

    public function test_from_dto_to_entity_mapeia_campos(): void
    {
        $dto = new UsuarioDto(
            id: '1',
            uuid: 'uuid-dto-456',
            nome: 'Carla Santos',
            email: 'carla@email.com',
            senha: 'senha-dto',
            ativo: true,
            perfil: 'atendente',
            criado_em: new DateTimeImmutable('2024-05-01 12:00:00'),
            atualizado_em: new DateTimeImmutable('2024-05-01 12:00:00'),
            deletado_em: null,
        );

        $entidade = $this->mapper->fromDtoToEntity($dto);

        $this->assertInstanceOf(Entidade::class, $entidade);
        $this->assertEquals('uuid-dto-456', $entidade->uuid);
        $this->assertEquals('Carla Santos', $entidade->nome);
        $this->assertEquals('carla@email.com', $entidade->email);
        $this->assertEquals('atendente', $entidade->perfil);
        $this->assertTrue($entidade->ativo);
    }
}
