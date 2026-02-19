<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Cliente;

use App\Domain\Entity\Cliente\Entidade;
use App\Domain\Entity\Cliente\Mapper;
use App\Models\ClienteModel;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new Mapper();
    }

    private function criarModelFake(array $dados = []): ClienteModel
    {
        $defaults = [
            'uuid'         => 'uuid-cliente-abc',
            'nome'         => 'Carlos Pereira',
            'documento'    => '98765432100',
            'email'        => 'carlos@email.com',
            'fone'         => '11977777777',
            'criado_em'    => '2024-06-01 08:00:00',
            'atualizado_em' => '2024-06-01 08:00:00',
            'deletado_em'  => null,
        ];

        $model = new ClienteModel();

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
        $this->assertEquals('uuid-cliente-abc', $entidade->uuid);
        $this->assertEquals('Carlos Pereira', $entidade->nome);
        $this->assertEquals('98765432100', $entidade->documento);
        $this->assertEquals('carlos@email.com', $entidade->email);
        $this->assertEquals('11977777777', $entidade->fone);
    }

    public function test_from_model_to_entity_mapeia_timestamps(): void
    {
        $model = $this->criarModelFake([
            'criado_em'    => '2024-03-15 12:30:00',
            'atualizado_em' => '2024-03-20 09:15:00',
        ]);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertEquals('2024-03-15 12:30:00', $entidade->criadoEm->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-03-20 09:15:00', $entidade->atualizadoEm->format('Y-m-d H:i:s'));
    }

    public function test_from_model_to_entity_deletado_em_null(): void
    {
        $model = $this->criarModelFake(['deletado_em' => null]);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertNull($entidade->deletadoEm);
    }

    public function test_from_model_to_entity_deletado_em_preenchido(): void
    {
        $model = $this->criarModelFake(['deletado_em' => '2024-07-01 10:00:00']);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertNotNull($entidade->deletadoEm);
        $this->assertEquals('2024-07-01 10:00:00', $entidade->deletadoEm->format('Y-m-d H:i:s'));
    }
}
