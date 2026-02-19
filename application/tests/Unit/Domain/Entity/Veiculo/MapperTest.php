<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Veiculo;

use App\Domain\Entity\Veiculo\Entidade;
use App\Domain\Entity\Veiculo\Mapper;
use App\Models\VeiculoModel;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new Mapper();
    }

    private function criarModelFake(array $dados = []): VeiculoModel
    {
        $defaults = [
            'uuid'          => 'uuid-veiculo-xyz',
            'marca'         => 'Volkswagen',
            'modelo'        => 'Gol',
            'placa'         => 'DEF5678',
            'ano'           => 2020,
            'cliente_id'    => 42,
            'criado_em'     => '2024-05-10 08:00:00',
            'atualizado_em' => '2024-05-10 08:00:00',
            'deletado_em'   => null,
        ];

        $model = new VeiculoModel();

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
        $this->assertEquals('uuid-veiculo-xyz', $entidade->uuid);
        $this->assertEquals('Volkswagen', $entidade->marca);
        $this->assertEquals('Gol', $entidade->modelo);
        $this->assertEquals('DEF5678', $entidade->placa);
        $this->assertEquals(2020, $entidade->ano);
        $this->assertEquals(42, $entidade->clienteId);
    }

    public function test_from_model_to_entity_mapeia_timestamps(): void
    {
        $model = $this->criarModelFake([
            'criado_em'     => '2024-04-01 09:00:00',
            'atualizado_em' => '2024-04-05 14:00:00',
        ]);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertEquals('2024-04-01 09:00:00', $entidade->criadoEm->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-04-05 14:00:00', $entidade->atualizadoEm->format('Y-m-d H:i:s'));
    }

    public function test_from_model_to_entity_deletado_em_null(): void
    {
        $model = $this->criarModelFake(['deletado_em' => null]);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertNull($entidade->deletadoEm);
    }

    public function test_from_model_to_entity_deletado_em_preenchido(): void
    {
        $model = $this->criarModelFake(['deletado_em' => '2024-08-15 10:30:00']);

        $entidade = $this->mapper->fromModelToEntity($model);

        $this->assertNotNull($entidade->deletadoEm);
        $this->assertEquals('2024-08-15 10:30:00', $entidade->deletadoEm->format('Y-m-d H:i:s'));
    }
}
