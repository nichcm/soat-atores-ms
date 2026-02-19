<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Veiculo;

use App\Domain\Entity\Veiculo\Entidade;
use App\Exception\DomainHttpException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EntidadeTest extends TestCase
{
    private int $anoValido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->anoValido = (int) date('Y');
    }

    private function criarEntidade(array $overrides = []): Entidade
    {
        $dados = array_merge([
            'uuid'         => 'uuid-veiculo-123',
            'marca'        => 'Toyota',
            'modelo'       => 'Corolla',
            'placa'        => 'ABC1234',
            'ano'          => $this->anoValido,
            'clienteId'    => 1,
            'criadoEm'     => new DateTimeImmutable('2024-01-01 10:00:00'),
            'atualizadoEm' => new DateTimeImmutable('2024-01-01 10:00:00'),
        ], $overrides);

        return new Entidade(
            uuid: $dados['uuid'],
            marca: $dados['marca'],
            modelo: $dados['modelo'],
            placa: $dados['placa'],
            ano: $dados['ano'],
            clienteId: $dados['clienteId'],
            criadoEm: $dados['criadoEm'],
            atualizadoEm: $dados['atualizadoEm'],
        );
    }

    public function test_criacao_com_dados_validos(): void
    {
        $entidade = $this->criarEntidade();

        $this->assertEquals('uuid-veiculo-123', $entidade->uuid);
        $this->assertEquals('Toyota', $entidade->marca);
        $this->assertEquals('Corolla', $entidade->modelo);
        $this->assertEquals('ABC1234', $entidade->placa);
        $this->assertEquals($this->anoValido, $entidade->ano);
        $this->assertEquals(1, $entidade->clienteId);
        $this->assertNull($entidade->deletadoEm);
    }

    public function test_ano_no_futuro_lanca_excecao(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Ano não pode ser maior que o ano atual');

        $this->criarEntidade(['ano' => $this->anoValido + 1]);
    }

    public function test_ano_atual_e_valido(): void
    {
        $entidade = $this->criarEntidade(['ano' => $this->anoValido]);
        $this->assertEquals($this->anoValido, $entidade->ano);
    }

    public function test_ano_passado_e_valido(): void
    {
        $entidade = $this->criarEntidade(['ano' => 2000]);
        $this->assertEquals(2000, $entidade->ano);
    }

    public function test_excluir_define_deletado_em(): void
    {
        $entidade = $this->criarEntidade();

        $this->assertNull($entidade->deletadoEm);
        $this->assertFalse($entidade->estaExcluido());

        $entidade->excluir();

        $this->assertNotNull($entidade->deletadoEm);
        $this->assertInstanceOf(DateTimeImmutable::class, $entidade->deletadoEm);
        $this->assertTrue($entidade->estaExcluido());
    }

    public function test_excluir_atualiza_atualizado_em(): void
    {
        $data = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade(['criadoEm' => $data, 'atualizadoEm' => $data]);

        $entidade->excluir();

        $this->assertGreaterThanOrEqual($data, $entidade->atualizadoEm);
    }

    public function test_esta_excluido_retorna_false_quando_nao_excluido(): void
    {
        $entidade = $this->criarEntidade();
        $this->assertFalse($entidade->estaExcluido());
    }

    public function test_to_http_response_retorna_campos_corretos(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toHttpResponse();

        $this->assertArrayHasKey('uuid', $res);
        $this->assertArrayHasKey('marca', $res);
        $this->assertArrayHasKey('modelo', $res);
        $this->assertArrayHasKey('placa', $res);
        $this->assertArrayHasKey('ano', $res);
        $this->assertArrayHasKey('criado_em', $res);
        $this->assertArrayHasKey('atualizado_em', $res);
        $this->assertArrayNotHasKey('cliente_id', $res);
    }

    public function test_to_create_data_array_inclui_cliente_id(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toCreateDataArray();

        $this->assertArrayHasKey('marca', $res);
        $this->assertArrayHasKey('modelo', $res);
        $this->assertArrayHasKey('placa', $res);
        $this->assertArrayHasKey('ano', $res);
        $this->assertArrayHasKey('cliente_id', $res);
        $this->assertArrayNotHasKey('uuid', $res);
    }

    public function test_atualizar_marca(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['marca' => 'Honda']);
        $this->assertEquals('Honda', $entidade->marca);
    }

    public function test_atualizar_modelo(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['modelo' => 'Civic']);
        $this->assertEquals('Civic', $entidade->modelo);
    }

    public function test_atualizar_placa(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['placa' => 'XYZ9999']);
        $this->assertEquals('XYZ9999', $entidade->placa);
    }

    public function test_atualizar_ano(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['ano' => 2020]);
        $this->assertEquals(2020, $entidade->ano);
    }

    public function test_atualizar_ano_invalido_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Ano não pode ser maior que o ano atual');

        $entidade->atualizar(['ano' => $this->anoValido + 5]);
    }

    public function test_atualizar_atualiza_atualizado_em(): void
    {
        $data = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade(['criadoEm' => $data, 'atualizadoEm' => $data]);

        $entidade->atualizar(['marca' => 'Fiat']);

        $this->assertGreaterThanOrEqual($data, $entidade->atualizadoEm);
    }

    public function test_to_update_data_array_nao_inclui_cliente_id(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toUpdateDataArray();

        $this->assertArrayHasKey('marca', $res);
        $this->assertArrayHasKey('modelo', $res);
        $this->assertArrayHasKey('placa', $res);
        $this->assertArrayHasKey('ano', $res);
        $this->assertArrayNotHasKey('cliente_id', $res);
    }

    public function test_to_external_retorna_campos_corretos(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toExternal();

        $this->assertArrayHasKey('uuid', $res);
        $this->assertArrayHasKey('marca', $res);
        $this->assertArrayHasKey('modelo', $res);
        $this->assertArrayHasKey('placa', $res);
        $this->assertArrayHasKey('ano', $res);
        $this->assertArrayNotHasKey('cliente_id', $res);
    }
}
